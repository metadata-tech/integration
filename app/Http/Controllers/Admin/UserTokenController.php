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
        return view('tokens.index', [
            'user' => $user
        ]);
    }
}