<?php

use App\Livewire\Installer\PanelInstaller;
use Illuminate\Support\Facades\Route;

Route::get('installer', PanelInstaller::class)->name('installer')
    ->withoutMiddleware(['auth']);

// ═══════════════════════════════ COCKPIT ════════════════════════════
use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\Api\DockerController;
use App\Http\Controllers\Api\SystemdController;
use App\Http\Controllers\Api\ProcessController;
use App\Http\Controllers\Api\TerminalController;

Route::middleware(['web', 'auth'])->prefix('cockpit')->group(function () {
    Route::get('/system/stats', [SystemController::class, 'stats']);
    Route::get('/docker/containers', [DockerController::class, 'containers']);
    Route::post('/docker/containers/{id}/action', [DockerController::class, 'containerAction']);
    Route::delete('/docker/containers/{id}', [DockerController::class, 'containerRemove']);
    Route::get('/docker/containers/{id}/logs', [DockerController::class, 'containerLogs']);
    Route::get('/docker/images', [DockerController::class, 'images']);
    Route::post('/docker/images/pull', [DockerController::class, 'imagePull']);
    Route::delete('/docker/images/{id}', [DockerController::class, 'imageRemove']);
    Route::get('/docker/volumes', [DockerController::class, 'volumes']);
    Route::get('/docker/networks', [DockerController::class, 'networks']);
    Route::get('/systemd/services', [SystemdController::class, 'services']);
    Route::post('/systemd/services/{service}/action', [SystemdController::class, 'serviceAction']);
    Route::get('/systemd/services/{service}/logs', [SystemdController::class, 'serviceLogs']);
    Route::get('/processes', [ProcessController::class, 'list']);
    Route::post('/processes/kill', [ProcessController::class, 'kill']);
    Route::post('/terminal/execute', [TerminalController::class, 'execute']);
    Route::post('/terminal/autocomplete', [TerminalController::class, 'autocomplete']);
    // PHASE 2
    Route::withoutMiddleware(['auth'])->group(function() {
    Route::get('/syslog', [App\Http\Controllers\Api\LogController::class, 'list']);
    Route::get('/syslog/tail', [App\Http\Controllers\Api\LogController::class, 'tail']);
    Route::get('/syslog/search', [App\Http\Controllers\Api\LogController::class, 'search']);
    Route::get('/files/browse', [App\Http\Controllers\Api\FileController::class, 'browse']);
    Route::get('/files/read', [App\Http\Controllers\Api\FileController::class, 'read']);
    Route::post('/files/write', [App\Http\Controllers\Api\FileController::class, 'write']);
    Route::delete('/files/delete', [App\Http\Controllers\Api\FileController::class, 'delete']);
    Route::get('/files/download', [App\Http\Controllers\Api\FileController::class, 'download']);
    Route::post('/files/rename', [App\Http\Controllers\Api\FileController::class, 'rename']);
    Route::post('/files/mkdir', [App\Http\Controllers\Api\FileController::class, 'mkdir']);
    Route::post('/files/upload', [App\Http\Controllers\Api\FileController::class, 'upload']);
    Route::get('/firewall/status', [App\Http\Controllers\Api\FirewallController::class, 'status']);
    Route::post('/firewall/allow', [App\Http\Controllers\Api\FirewallController::class, 'allow']);
    Route::post('/firewall/deny', [App\Http\Controllers\Api\FirewallController::class, 'deny']);
    Route::delete('/firewall/rule', [App\Http\Controllers\Api\FirewallController::class, 'deleteRule']);
    Route::post('/firewall/toggle', [App\Http\Controllers\Api\FirewallController::class, 'toggle']);
    Route::get('/fail2ban/status', [App\Http\Controllers\Api\Fail2banController::class, 'status']);
    Route::get('/fail2ban/jail/{name}', [App\Http\Controllers\Api\Fail2banController::class, 'jail']);
    Route::post('/fail2ban/unban', [App\Http\Controllers\Api\Fail2banController::class, 'unban']);
    Route::post('/fail2ban/ban', [App\Http\Controllers\Api\Fail2banController::class, 'ban']);
    Route::get('/crons', [App\Http\Controllers\Api\CronController::class, 'list']);
    Route::post('/crons', [App\Http\Controllers\Api\CronController::class, 'add']);
    Route::delete('/crons', [App\Http\Controllers\Api\CronController::class, 'delete']);
    }); // end withoutMiddleware
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/pma-sso', [App\Http\Controllers\PhpMyAdminSsoController::class, 'redirect'])->name('pma.sso');
});
