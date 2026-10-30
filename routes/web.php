<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');
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
Route::livewire('/admin/settings', 'admin.settings')
    ->middleware('auth')
    ->name('admin.settings');
Route::livewire('/admin/api', 'admin.api')
    ->middleware('auth')
    ->name('admin.api');
Route::livewire('/dashboard', 'agency.dashboard')
    ->middleware('auth')
    ->name('agency.dashboard');

Route::livewire('/properties', 'agency.properties')
    ->middleware('auth')
    ->name('agency.properties');

Route::livewire('/properties/create', 'admin.property-create')
    ->middleware('auth')
    ->name('agency.properties.create');
Route::livewire('/properties/{property}/edit', 'agency.property-edit')
    ->middleware('auth')
    ->name('agency.properties.edit');

Route::livewire('/properties/{property}/view', 'agency.property-view')
    ->middleware('auth')
    ->name('agency.properties.view');
Route::livewire('/property-network', 'admin.exchange')
    ->middleware('auth')
    ->name('agency.property-network');

Route::livewire('/syndications', 'agency.syndications')
    ->middleware('auth')
    ->name('agency.syndications');

Route::livewire('/received-properties', 'agency.syndications')
    ->middleware('auth')
    ->name('agency.received-properties');

Route::livewire('/shared-properties', 'agency.syndications')
    ->middleware('auth')
    ->name('agency.shared-properties');

Route::livewire('/team-agents', 'agency.team-agents')
    ->middleware('auth')
    ->name('agency.team-agents');

Route::livewire('/agency-directory', 'agency.agency-directory')
    ->middleware('auth')
    ->name('agency.agency-directory');

Route::livewire('/my-account', 'agency.my-account')
    ->middleware('auth')
    ->name('agency.my-account');

Route::livewire('/company-profile', 'agency.company-profile')
    ->middleware('auth')
    ->name('agency.company-profile');

Route::livewire('/sharing-terms', 'agency.sharing-terms')
    ->middleware('auth')
    ->name('agency.sharing-terms');

Route::livewire('/connector-api', 'agency.connector-api')
    ->middleware('auth')
    ->name('agency.connector-api');
