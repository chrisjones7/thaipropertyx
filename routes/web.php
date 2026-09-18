<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::livewire('/login', 'auth.login')
    ->name('login');

Route::livewire('/admin', 'admin.dashboard')
    ->middleware('auth')
    ->name('admin.dashboard');

Route::livewire('/admin/agencies', 'admin.agencies')
    ->middleware('auth')
    ->name('admin.agencies');

Route::livewire('/admin/agencies/create', 'admin.agency-create')
    ->middleware('auth')
    ->name('admin.agencies.create');

Route::livewire('/admin/agencies/{agency}/edit', 'admin.agency-edit')
    ->middleware('auth')
    ->name('admin.agencies.edit');

Route::livewire('/admin/users', 'admin.users')
    ->middleware('auth')
    ->name('admin.users');

Route::livewire('/admin/users/create', 'admin.user-create')
    ->middleware('auth')
    ->name('admin.users.create');

Route::livewire('/admin/users/{user}/edit', 'admin.user-edit')
    ->middleware('auth')
    ->name('admin.users.edit');

Route::livewire('/admin/properties', 'admin.properties')
    ->middleware('auth')
    ->name('admin.properties');

Route::livewire('/admin/properties/create', 'admin.property-create')
    ->middleware('auth')
    ->name('admin.properties.create');

Route::livewire('/admin/properties/{property}/edit', 'admin.property-edit')
    ->middleware('auth')
    ->name('admin.properties.edit');

Route::livewire('/admin/exchange', 'admin.exchange')
    ->middleware('auth')
    ->name('admin.exchange');
Route::livewire('/admin/syndications', 'admin.syndications')
    ->middleware('auth')
    ->name('admin.syndications');
Route::livewire('/dashboard', 'agency.dashboard')
    ->middleware('auth')
    ->name('agency.dashboard');

Route::livewire('/company-profile', 'agency.company-profile')
    ->middleware('auth')
    ->name('agency.company-profile');

Route::livewire('/sharing-terms', 'agency.sharing-terms')
    ->middleware('auth')
    ->name('agency.sharing-terms');
