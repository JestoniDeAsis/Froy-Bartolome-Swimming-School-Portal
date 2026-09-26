<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $roleId = Auth::user()->role_id;

        $roleMenus = config('role_menus');

        if (!isset($roleMenus[$roleId])) {
            abort(403, 'Access denied: no menu configured for this role.');
        }

        $menuItems = $roleMenus[$roleId];

        $allowedPages = array_map(fn($item) => $item['view'], $menuItems);

        $page = $request->session()->get('current_page', array_key_first($allowedPages));

        if (!isset($allowedPages[$page])) {
            $page = array_key_first($allowedPages);
        }

        $profile = DB::table('tbl_user_profile')
            ->where('user_id', Auth::id())
            ->first();

        $roleName = DB::table('tbl_roles')
            ->where('role_id', $roleId)
            ->value('role_name'); // adjust column name if different in your tbl_roles

        $viewData = [
            'menuItems' => $menuItems,
            'page' => $page,
            'pageView' => $allowedPages[$page],
            'profile' => $profile,
            'roleName' => $roleName,
        ];

        if ($page === 'graduation') {
            $viewData['graduationTabs'] = (new GraduationController())->graduationTabs();
        }

        return view('home.index', $viewData);
    }

    public function navigate(Request $request, string $page)
    {
        $roleId = Auth::user()->role_id;
        $roleMenus = config('role_menus');

        if (!isset($roleMenus[$roleId])) {
            abort(403, 'Access denied: no menu configured for this role.');
        }

        $allowedPages = array_map(fn($item) => $item['view'], $roleMenus[$roleId]);

        if (isset($allowedPages[$page])) {
            $request->session()->put('current_page', $page);
        }

        return redirect()->route('home');
    }
}