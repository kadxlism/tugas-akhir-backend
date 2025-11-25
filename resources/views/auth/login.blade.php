@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="flex items-center justify-center min-h-screen">
    <form method="POST" action="{{ route('login') }}" class="bg-white p-6 rounded shadow-md w-80">
        @csrf
        <h2 class="text-xl font-semibold mb-4">Login</h2>
        <input name="email" type="email" placeholder="Email" required
            class="w-full mb-3 p-2 border border-gray-300 rounded" />
        <input name="password" type="password" placeholder="Password" required
            class="w-full mb-3 p-2 border border-gray-300 rounded" />
        <button type="submit"
            class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">Login</button>
    </form>
</div>
@endsection
