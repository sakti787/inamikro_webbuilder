<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\AdminApiController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Display the Panel Pendamping Admin workspace for the authenticated admin user.
     */
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $initialBootstrap = AdminApiController::bootstrapPayload();

        $adminConfig = [
            'userName' => $user->name,
            'userEmail' => $user->email,
            'csrfToken' => csrf_token(),
            'routes' => [
                'bootstrap' => route('api.admin.bootstrap'),
                'logs' => route('api.admin.logs'),
                'umkmBase' => url('/api/admin/umkm'),
                'templatesBase' => url('/api/admin/templates'),
                'autoApprove' => route('api.admin.settings.auto-approve'),
                'contact' => route('api.admin.settings.contact'),
                'logout' => route('logout'),
            ],
            'initialData' => $initialBootstrap,
        ];

        return view('admin.index', [
            'user' => $user,
            'adminConfig' => $adminConfig,
        ]);
    }
}
