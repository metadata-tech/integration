<?php
// app/Http/Controllers/Admin/UserTokenController.php
namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Jetstream\Jetstream;

class UserTokenController
{
    // Show token management interface for a user
    public function index(User $user)
    {
        return view('api.index', [
            'user' => $user,
            'tokens' => $user->tokens,
            'availablePermissions' => Jetstream::$permissions,
            'defaultPermissions' => Jetstream::$defaultPermissions,
        ]);
    }

    // Create new token for a user
    public function store(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $token = $user->createToken(
            $request->name,
            Jetstream::validPermissions($request->input('permissions', []))
        );

        return back()->with('flash', [
            'token' => explode('|', $token->plainTextToken, 2)[1],
        ]);
    }

    // Update token permissions
    public function update(Request $request, User $user, $tokenId)
    {
        $token = $user->tokens()->where('id', $tokenId)->firstOrFail();

        $token->forceFill([
            'abilities' => Jetstream::validPermissions($request->input('permissions', [])),
        ])->save();

        return back(303);
    }

    // Delete token
    public function destroy(User $user, $tokenId)
    {
        $user->tokens()->where('id', $tokenId)->delete();
        return back(303);
    }
}