<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Carbon;

class RegistersController extends Controller
{
    public function data(): array
    {
        $users = User::where('role_id', 3)
            ->with('profile')
            ->get();

        return [
            'registrations' => $users->map(fn ($u) => [
                'id'         => $u->user_id,
                'lastName'   => $u->profile->last_name ?? '',
                'firstName'  => $u->profile->first_name ?? '',
                'middleName' => $u->profile->middle_name ?? '',
                'email'      => $u->email_address,
                'birthdate'  => $u->profile->birthdate
                    ? Carbon::parse($u->profile->birthdate)->format('M-d-Y')
                    : '',
                'age' => $u->profile->birthdate
                    ? Carbon::parse($u->profile->birthdate)->age
                    : '',
            ]),
        ];
    }
}