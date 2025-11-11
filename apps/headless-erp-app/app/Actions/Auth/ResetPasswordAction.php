<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Contracts\UserRepositoryContract;
use App\Events\Auth\PasswordResetEvent;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Reset Password Action
 *
 * Resets user password using a secure one-time token.
 */
class ResetPasswordAction
{
    use AsAction;

    /**
     * Create a new action instance
     */
    public function __construct(
        private readonly UserRepositoryContract $userRepository
    ) {}

    /**
     * Reset user password with token
     *
     * @param  string  $email  User's email address
     * @param  string  $token  Password reset token
     * @param  string  $newPassword  New password
     * @return bool True if password reset successful
     *
     * @throws \RuntimeException If token is invalid or expired
     */
    public function handle(string $email, string $token, string $newPassword): bool
    {
        // Find token record
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (! $resetRecord) {
            throw new \RuntimeException('Invalid or expired reset token');
        }

        // Verify token (comparing hashed version)
        if (! hash_equals($resetRecord->token, hash('sha256', $token))) {
            throw new \RuntimeException('Invalid or expired reset token');
        }

        // Check if token is expired (1 hour)
        $expiresAt = now()->subHour();
        if ($resetRecord->created_at < $expiresAt) {
            // Delete expired token
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            throw new \RuntimeException('Reset token has expired');
        }

        // Find user (using first available tenant for this email)
        $user = DB::table('users')->where('email', $email)->first();
        
        if (! $user) {
            throw new \RuntimeException('User not found');
        }

        $userModel = $this->userRepository->findById($user->id);

        if (! $userModel) {
            throw new \RuntimeException('User not found');
        }

        // Update password
        $this->userRepository->update($userModel, [
            'password' => $newPassword,
        ]);

        // Delete used token (single-use)
        DB::table('password_reset_tokens')->where('email', $email)->delete();

        // Reset failed login attempts
        $userModel->resetFailedLoginAttempts();

        // Dispatch password reset event
        event(new PasswordResetEvent($userModel));

        return true;
    }
}
