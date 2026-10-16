<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Masuk ke Tofunime - Admin Studio')]
class Login extends Component
{
    #[Validate('required|email', message: [
        'required' => 'Alamat email wajib diisi.',
        'email' => 'Format email tidak valid.',
    ])]
    public string $email = '';

    #[Validate('required|min:6', message: [
        'required' => 'Kata sandi wajib diisi.',
        'min' => 'Kata sandi minimal 6 karakter.',
    ])]
    public string $password = '';

    public bool $remember = false;

    public function mount(): mixed
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return null;
    }

    public function authenticate(): mixed
    {
        $this->validate();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->addError('email', 'Email atau kata sandi yang Anda masukkan tidak sesuai.');

            return null;
        }

        session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
