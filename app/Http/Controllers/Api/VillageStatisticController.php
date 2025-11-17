<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\VillageAccessDeniedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportVillageStatisticsRequest;
use App\Http\Requests\StoreVillageStatisticRequest;
use App\Http\Requests\UpdateVillageStatisticRequest;
use App\Http\Resources\VillageStatisticResource;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Village;
use App\Models\VillageStatistic;
use App\Services\ActivityLogger;
use App\Services\VillageStatisticService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VillageStatisticController extends Controller
{
    public function __construct(
        private VillageStatisticService $service,
    ) {}

    /**
     * @OA\Get(
     *     path="/villages/{village}/statistics",
     *     summary="List village statistics",
     *     description="Get paginated list of village statistics with optional filters",
     *     tags={"Village Statistics"},
     *     @OA\Parameter(
     *         name="village",
     *         in="path",
     *         required=true,
     *         description="Village ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Filter by year",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="statistic_type_id",
     *         in="query",
     *         description="Filter by statistic type",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page (max 100)",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items()),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request, Village $village): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $perPage = $perPage > 0 ? min($perPage, 100) : 15;

        $statistics = VillageStatistic::query()
            ->select([
                'id',
                'village_id',
                'statistic_type_id',
                'indicator_name',
                'value',
                'unit',
                'year',
                'period',
                'source',
                'notes',
                'created_by',
                'updated_by',
                'created_at',
                'updated_at'
            ])
            ->with([
                'statisticType:id,name,code,category,description,display_order',
                'creator:id,full_name,name'
            ])
            ->where('village_id', $village->id)
            ->when($request->filled('year'), fn($query) => $query->where('year', $request->query('year')))
            ->when($request->filled('statistic_type_id'), fn($query) => $query->where('statistic_type_id', $request->query('statistic_type_id')))
            ->orderByDesc('year')
            ->orderBy('indicator_name')
            ->paginate($perPage)
            ->appends($request->query());

        $data = VillageStatisticResource::collection($statistics->getCollection())->resolve();

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $statistics->currentPage(),
                'per_page' => $statistics->perPage(),
                'total' => $statistics->total(),
                'last_page' => $statistics->lastPage(),
            ],
        ]);
    }

    public function summary(Request $request, Village $village): JsonResponse
    {
        $year = $request->query('year');

        $statistics = VillageStatistic::query()
            ->select(['id', 'village_id', 'statistic_type_id', 'indicator_name', 'value', 'unit', 'year'])
            ->with('statisticType:id,name,code,category,description,display_order')
            ->where('village_id', $village->id)
            ->when($year, fn($query) => $query->where('year', $year))
            ->get();

        $effectiveYear = $year ?? $statistics->max('year');

        $categories = $statistics
            ->groupBy(function (VillageStatistic $statistic) {
                return $statistic->statisticType?->category ?? 'lainnya';
            })
            ->map(function (Collection $items) {
                return [
                    'total_indicators' => $items->count(),
                    'statistics' => $items->map(function (VillageStatistic $statistic) {
                        return [
                            'indicator_name' => $statistic->indicator_name,
                            'value' => $statistic->value !== null ? (float) $statistic->value : null,
                            'unit' => $statistic->unit,
                        ];
                    })->values(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $effectiveYear,
                'categories' => $categories,
            ],
        ]);
    }

    public function store(StoreVillageStatisticRequest $request, Village $village): JsonResponse
    {
        $user = $this->user();
        $this->authorizeVillageAccess($village, $user);

        $data = $request->validated();

        $statistic = VillageStatistic::create([
            'village_id' => $village->id,
            'statistic_type_id' => $data['statistic_type_id'],
            'indicator_name' => $data['indicator_name'],
            'value' => $data['value'],
            'unit' => $data['unit'] ?? null,
            'year' => $data['year'],
            'period' => $data['period'] ?? null,
            'source' => $data['source'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $user->id,
        ]);

        $statistic->load([
            'statisticType:id,name,code,category,description,display_order',
            'creator:id,full_name,name'
        ]);

        ActivityLogger::log(
            'create',
            $statistic,
            sprintf('Menambahkan data statistik %s', $statistic->indicator_name),
            ['new_data' => $statistic->toArray()]
        );

        return response()->json([
            'success' => true,
            'message' => 'Data statistik berhasil ditambahkan',
            'data' => VillageStatisticResource::make($statistic),
        ], 201);
    }

    public function update(UpdateVillageStatisticRequest $request, Village $village, int $statistic): JsonResponse
    {
        $user = $this->user();
        $this->authorizeVillageAccess($village, $user);

        $statistic = $this->findStatisticOrFail($village, $statistic);

        $data = $request->validated();
        $original = $statistic->toArray();
        $statistic->fill($data);
        $statistic->updated_by = $user->id;
        $statistic->save();
        $statistic->load([
            'statisticType:id,name,code,category,description,display_order',
            'creator:id,full_name,name',
            'updater:id,full_name,name'
        ]);

        ActivityLogger::log(
            'update',
            $statistic,
            sprintf('Memperbarui data statistik %s', $statistic->indicator_name),
            [
                'old_data' => $original,
                'new_data' => $statistic->toArray(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Data statistik berhasil diperbarui',
            'data' => VillageStatisticResource::make($statistic),
        ]);
    }

    public function destroy(Village $village, int $statistic): JsonResponse
    {
        $user = $this->user();
        $this->authorizeVillageAccess($village, $user);

        $statistic = $this->findStatisticOrFail($village, $statistic);
        $snapshot = $statistic->toArray();

        ActivityLogger::log(
            'delete',
            $statistic,
            sprintf('Menghapus data statistik %s', $statistic->indicator_name),
            ['old_data' => $snapshot]
        );

        $statistic->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data statistik berhasil dihapus',
        ]);
    }

    public function import(ImportVillageStatisticsRequest $request, Village $village): JsonResponse
    {
        $user = $this->user();
        $this->authorizeVillageAccess($village, $user);

        $summary = $this->service->import($village, $request->file('file'), $user);

        return response()->json([
            'success' => true,
            'message' => 'Data statistik berhasil diimpor',
            'data' => $summary,
        ]);
    }

    public function export(Request $request, Village $village): BinaryFileResponse|JsonResponse
    {
        $year = $request->query('year');
        $format = $request->query('format', 'csv');

        try {
            return $this->service->export($village, $format, $year ? (int) $year : null);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $message = Arr::first(Arr::flatten($e->errors())) ?? $e->getMessage();

            return response()->json([
                'success' => false,
                'error' => 'VALIDATION_ERROR',
                'message' => $message,
                'errors' => $e->errors(),
            ], $e->status ?? 422);
        }
    }

    protected function user(): User
    {
        $user = auth()->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }

    protected function authorizeVillageAccess(Village $village, User $user): void
    {
        $role = $user->role?->role_name;

        if ($role === UserRole::BPS_ADMIN) {
            return;
        }

        if ($role === UserRole::VILLAGE_OFFICER && (int) $user->village_id === (int) $village->id) {
            return;
        }

        throw new VillageAccessDeniedException();
    }

    protected function findStatisticOrFail(Village $village, int $statisticId): VillageStatistic
    {
        return VillageStatistic::where('village_id', $village->id)->findOrFail($statisticId);
    }
}
