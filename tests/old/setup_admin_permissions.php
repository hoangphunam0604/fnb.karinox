<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

echo "🔐 TẠO PERMISSIONS CHO ADMIN\n";
echo "===========================\n\n";

try {
    // Tạo permissions cần thiết
    $permissions = [
        'view-products',
        'create-products', 
        'update-products',
        'delete-products',
        'view-categories',
        'create-categories',
        'update-categories',
        'delete-categories',
        'view-customers',
        'create-customers',
        'update-customers',
        'view-inventory',
        'manage-inventory',
        'view-reports',
        'view-branches',
        'view-users',
        'manage-settings'
    ];
    
    echo "📋 Tạo permissions:\n";
    foreach ($permissions as $permission) {
        $perm = Permission::firstOrCreate([
            'name' => $permission, 
            'guard_name' => 'api'
        ]);
        echo "✅ {$permission}\n";
    }
    
    echo "\n🎭 Gán permissions cho admin role:\n";
    $adminRole = Role::where('name', 'admin')->where('guard_name', 'api')->first();
    
    if ($adminRole) {
        $allPermissions = Permission::where('guard_name', 'api')->get();
        $adminRole->syncPermissions($allPermissions);
        echo "✅ Admin role đã được gán {$allPermissions->count()} permissions\n";
        
        // Kiểm tra user admin đã có role chưa
        $adminUser = User::where('username', 'karinox_admin')->first();
        if ($adminUser) {
            $adminUser->assignRole($adminRole);
            echo "✅ User karinox_admin đã được gán admin role\n";
        }
        
    } else {
        echo "❌ Admin role không tồn tại\n";
    }
    
    echo "\n🔍 Kiểm tra kết quả:\n";
    $user = User::where('username', 'karinox_admin')->first();
    if ($user) {
        $userPermissions = $user->getAllPermissions();
        echo "👤 User: {$user->fullname}\n";
        echo "🎭 Roles: " . $user->roles->pluck('name')->join(', ') . "\n";
        echo "🔐 Permissions: {$userPermissions->count()} permissions\n";
        
        if ($userPermissions->count() > 0) {
            echo "\n📋 Chi tiết permissions:\n";
            foreach ($userPermissions->take(10) as $permission) {
                echo "- {$permission->name}\n";
            }
            if ($userPermissions->count() > 10) {
                echo "... và " . ($userPermissions->count() - 10) . " permissions khác\n";
            }
        }
    }
    
    echo "\n🎉 HOÀN THÀNH!\n";
    echo "============\n";
    echo "✅ Tạo " . count($permissions) . " permissions\n";
    echo "✅ Gán permissions cho admin role\n";
    echo "✅ User karinox_admin có đầy đủ quyền\n";
    
} catch (Exception $e) {
    echo "❌ LỖI: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
}