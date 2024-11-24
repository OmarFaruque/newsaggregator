<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * PasswordResetController
 * 
 * This controller manages the process of password reset for users. 
 * It includes functionality to send a password reset link to the user's email and to handle password reset requests.
 * 
 * Routes:
 * - `POST /password/email` triggers `sendResetLinkEmail` to send a password reset link.
 * - `POST /password/reset` triggers `reset` to reset the user's password.
 */
class PasswordResetController extends Controller
{
    /**
     * @OA\Post(
     *     path="/password/email",
     *     summary="Send Password Reset Link",
     *     description="Sends a password reset link to the user's email address if the email is registered.",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"email"},
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 description="The email address associated with the user's account."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset link sent successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Reset link sent to your email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed for the provided input.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 description="Details of the validation errors."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error or failure to send reset link.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Unable to send reset link")
     *         )
     *     )
     * )
     */
    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Reset link sent to your email'])
            : response()->json(['error' => 'Unable to send reset link'], 500);
    }


    /**
     * @OA\Post(
     *     path="/password/reset",
     *     summary="Reset User Password",
     *     description="Allows users to reset their password using a valid reset token, email, and new password. 
     *                  The new password must meet the specified criteria and be confirmed.",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"token", "email", "password", "password_confirmation"},
     *             @OA\Property(
     *                 property="token",
     *                 type="string",
     *                 description="The password reset token received by the user."
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 description="The email address associated with the user's account."
     *             ),
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 format="password",
     *                 description="The new password for the user. Must be at least 8 characters and confirmed."
     *             ),
     *             @OA\Property(
     *                 property="password_confirmation",
     *                 type="string",
     *                 format="password",
     *                 description="The confirmation of the new password. Must match the 'password' field."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password has been reset successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Password has been reset")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed for the provided input.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 description="Details of the validation errors."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error or failure to reset the password.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Failed to reset password")
     *         )
     *     ),
     *     security={
     *         {"sanctum": {}}
     *     }
     * )
     */
    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password has been reset'])
            : response()->json(['error' => 'Failed to reset password'], 500);
    }
}
