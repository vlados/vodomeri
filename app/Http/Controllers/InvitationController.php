<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class InvitationController extends Controller
{
    /**
     * Show the invitation acceptance form
     */
    public function showAcceptForm(string $code)
    {
        $invitation = Invitation::where('code', $code)->firstOrFail();
        
        if ($invitation->isExpired()) {
            return redirect()->route('welcome')->with('error', 'This invitation has expired.');
        }
        
        if ($invitation->isUsed()) {
            return redirect()->route('welcome')->with('error', 'This invitation has already been used.');
        }
        
        return view('auth.accept-invitation', [
            'invitation' => $invitation,
        ]);
    }
    
    /**
     * Process the invitation acceptance
     */
    public function accept(Request $request, string $code)
    {
        $invitation = Invitation::where('code', $code)->firstOrFail();
        
        if ($invitation->isExpired()) {
            return redirect()->route('welcome')->with('error', 'This invitation has expired.');
        }
        
        if ($invitation->isUsed()) {
            return redirect()->route('welcome')->with('error', 'This invitation has already been used.');
        }
        
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);
        
        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $invitation->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(), // Auto-verify
        ]);
        
        // Assign resident role if the method exists (from Spatie Permission)
        if (method_exists($user, 'assignRole')) {
            $user->assignRole('resident');
        }
        
        // Attach apartment
        $user->apartments()->attach($invitation->apartment_id);
        
        // Mark invitation as used
        $invitation->markAsUsed();
        
        // Log in the user without requiring verification
        Auth::login($user);
        
        // Ensure verification flag is set in the session to bypass verification middleware
        session(['auth.password_confirmed_at' => time()]);
        
        return redirect()->route('dashboard')->with('verified', true);
    }
}