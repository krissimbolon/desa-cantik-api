<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * Shared schema references used across the Desa Cantik API documentation.
 */
class Schemas
{
    /**
     * @OA\Schema(
     *     schema="ErrorResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=false),
     *     @OA\Property(property="message", type="string", example="Login failed"),
     *     @OA\Property(property="errors", type="object", nullable=true, example={"login": {"Invalid credentials"}})
     * )
     */
    public static function errorResponse()
    {
    }

    /**
     * @OA\Schema(
     *     schema="ValidationErrorResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=false),
     *     @OA\Property(property="message", type="string", example="Validation failed"),
     *     @OA\Property(
     *         property="errors",
     *         type="object",
     *         example={"email": {"The email has already been taken."}}
     *     )
     * )
     */
    public static function validationErrorResponse()
    {
    }

    /**
     * @OA\Schema(
     *     schema="AuthUser",
     *     type="object",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="username", type="string", example="admin"),
     *     @OA\Property(property="email", type="string", example="admin@desa.id"),
     *     @OA\Property(property="full_name", type="string", example="Admin Desa Cantik"),
     *     @OA\Property(property="phone", type="string", example="+628123456789"),
     *     @OA\Property(property="is_active", type="boolean", example=true),
     *     @OA\Property(
     *         property="role",
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="role_name", type="string", example="bps_admin"),
     *         @OA\Property(property="display_name", type="string", example="Admin BPS")
     *     ),
     *     @OA\Property(
     *         property="village",
     *         type="object",
     *         nullable=true,
     *         @OA\Property(property="id", type="integer", example=10),
     *         @OA\Property(property="name", type="string", example="Desa Nonongan Selatan"),
     *         @OA\Property(property="kode", type="string", example="7310022001")
     *     )
     * )
     */
    public static function authUser()
    {
    }

    /**
     * @OA\Schema(
     *     schema="AuthResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", example="Login successful"),
     *     @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="user", ref="#/components/schemas/AuthUser"),
     *         @OA\Property(property="token", type="string", example="1|uYtA..."),
     *         @OA\Property(property="token_type", type="string", example="Bearer")
     *     )
     * )
     */
    public static function authResponse()
    {
    }

    /**
     * @OA\Schema(
     *     schema="PasswordResetLinkResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", example="Password reset link sent to your email"),
     *     @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="reset_token", type="string", example="G63h1..."),
     *         @OA\Property(property="email", type="string", format="email", example="admin@desa.id")
     *     )
     * )
     */
    public static function passwordResetLinkResponse()
    {
    }

    /**
     * @OA\Schema(
     *     schema="TokenResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", example="Token refreshed successfully"),
     *     @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="token", type="string", example="1|uYtA..."),
     *         @OA\Property(property="token_type", type="string", example="Bearer")
     *     )
     * )
     */
    public static function tokenResponse()
    {
    }

    /**
     * @OA\Schema(
     *     schema="UserProfileResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *     @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="id", type="integer", example=1),
     *         @OA\Property(property="username", type="string", example="admin"),
     *         @OA\Property(property="email", type="string", example="admin@desa.id"),
     *         @OA\Property(property="full_name", type="string", example="Admin Desa Cantik"),
     *         @OA\Property(property="phone", type="string", example="+628123456789")
     *     )
     * )
     */
    public static function userProfileResponse()
    {
    }

    /**
     * @OA\Schema(
     *     schema="MessageResponse",
     *     type="object",
     *     @OA\Property(property="success", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", example="Operation completed")
     * )
     */
    public static function messageResponse()
    {
    }

    /**
     * @OA\Schema(
     *     schema="RegisterRequest",
     *     type="object",
     *     required={"username","email","password","password_confirmation","role_id"},
     *     @OA\Property(property="username", type="string", example="desa_admin"),
     *     @OA\Property(property="email", type="string", format="email", example="admin@desa.id"),
     *     @OA\Property(property="password", type="string", format="password", example="secret123"),
     *     @OA\Property(property="password_confirmation", type="string", format="password", example="secret123"),
     *     @OA\Property(property="role_id", type="integer", example=2),
     *     @OA\Property(property="village_id", type="integer", nullable=true, example=10),
     *     @OA\Property(property="full_name", type="string", nullable=true, example="Admin Desa Nonongan"),
     *     @OA\Property(property="phone", type="string", nullable=true, example="+628123456789")
     * )
     */
    public static function registerRequest()
    {
    }

    /**
     * @OA\Schema(
     *     schema="LoginRequest",
     *     type="object",
     *     required={"password"},
     *     @OA\Property(property="login", type="string", example="admin@desa.id", description="Email or username"),
     *     @OA\Property(property="username", type="string", example="admin", description="Alternative to `login`"),
     *     @OA\Property(property="password", type="string", format="password", example="secret123")
     * )
     */
    public static function loginRequest()
    {
    }

    /**
     * @OA\Schema(
     *     schema="ForgotPasswordRequest",
     *     type="object",
     *     required={"email"},
     *     @OA\Property(property="email", type="string", format="email", example="admin@desa.id")
     * )
     */
    public static function forgotPasswordRequest()
    {
    }

    /**
     * @OA\Schema(
     *     schema="ResetPasswordRequest",
     *     type="object",
     *     required={"email","token","password","password_confirmation"},
     *     @OA\Property(property="email", type="string", format="email", example="admin@desa.id"),
     *     @OA\Property(property="token", type="string", example="123456"),
     *     @OA\Property(property="password", type="string", format="password", example="secret123"),
     *     @OA\Property(property="password_confirmation", type="string", format="password", example="secret123")
     * )
     */
    public static function resetPasswordRequest()
    {
    }

    /**
     * @OA\Schema(
     *     schema="UpdateProfileRequest",
     *     type="object",
     *     @OA\Property(property="full_name", type="string", example="Admin Desa Cantik"),
     *     @OA\Property(property="phone", type="string", example="+628123456789"),
     *     @OA\Property(property="email", type="string", format="email", example="admin@desa.id")
     * )
     */
    public static function updateProfileRequest()
    {
    }

    /**
     * @OA\Schema(
     *     schema="UpdatePasswordRequest",
     *     type="object",
     *     required={"current_password","new_password","new_password_confirmation"},
     *     @OA\Property(property="current_password", type="string", format="password", example="old-secret"),
     *     @OA\Property(property="new_password", type="string", format="password", example="new-secret123"),
     *     @OA\Property(property="new_password_confirmation", type="string", format="password", example="new-secret123")
     * )
     */
    public static function updatePasswordRequest()
    {
    }

    /**
     * @OA\Schema(
     *     schema="VillageResource",
     *     type="object",
     *     @OA\Property(property="id", type="string", example="10"),
     *     @OA\Property(property="name", type="string", example="Desa Nonongan Selatan"),
     *     @OA\Property(property="district", type="string", example="Rantebua"),
     *     @OA\Property(property="regency", type="string", example="Toraja Utara"),
     *     @OA\Property(property="province", type="string", example="Sulawesi Selatan"),
     *     @OA\Property(property="population", type="integer", example=5310),
     *     @OA\Property(property="status", type="string", example="Aktif"),
     *     @OA\Property(property="image", type="string", format="uri", example="https://cdn.desacantik.id/images/nonongan.jpg"),
     *     @OA\Property(property="area", type="number", format="float", example=17.2),
     *     @OA\Property(property="households", type="integer", example=1200),
     *     @OA\Property(property="malePopulation", type="integer", example=2600),
     *     @OA\Property(property="femalePopulation", type="integer", example=2710)
     * )
     */
    public static function villageResource()
    {
    }

    /**
     * @OA\Schema(
     *     schema="VillageCreateRequest",
     *     type="object",
     *     required={"name","location"},
     *     @OA\Property(property="name", type="string", example="Desa Baru"),
     *     @OA\Property(property="location", type="string", example="Toraja Utara"),
     *     @OA\Property(property="kecamatan", type="string", nullable=true, example="Rantebua"),
     *     @OA\Property(property="kabupaten", type="string", nullable=true, example="Toraja Utara"),
     *     @OA\Property(property="provinsi", type="string", nullable=true, example="Sulawesi Selatan")
     * )
     */
    public static function villageCreateRequest()
    {
    }
}
