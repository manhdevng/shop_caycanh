<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Danh sách 3 nhóm danh mục cha mới + tên các danh mục con (đang nằm dưới
     * root "Kiểu loại cây") sẽ được chuyển sang mỗi nhóm. Dùng tên để tra cứu
     * thay vì hardcode id, vì id có thể khác nhau giữa các môi trường.
     */
    private function groups(): array
    {
        return [
            'Cây cảnh trong nhà & để bàn' => [
                'Cây phong thủy hút tài lộc',
                'Cây dễ chăm sóc',
                'Cây lọc không khí',
            ],
            'Cây cảnh văn phòng' => [
                'Cây kích thước trung bình/lớn',
            ],
            'Cây cảnh sân vườn & ngoài trời' => [
                'Cây bóng mát & sân vườn',
                'Cây trồng viền & trang trí lối đi',
            ],
        ];
    }

    /**
     * Tái cấu trúc cây danh mục "Kiểu loại cây": tách 6 danh mục con hiện có
     * thành 3 nhóm gốc mới (mức 1) để trang danh mục dễ điều hướng hơn.
     *
     * - Không đụng tới bảng category_product: sản phẩm vẫn gắn nguyên với
     *   6 danh mục con đó, chỉ đổi parent_id của chính các danh mục con.
     * - Không đụng tới root "Kiểu loại hoa" (id 20) và 4 con của nó.
     * - Nếu sau khi chuyển hết 6 con, root "Kiểu loại cây" không còn con nào
     *   VÀ không còn sản phẩm nào gắn trực tiếp vào nó, thì xoá root này
     *   (nó trở thành thừa). Nếu có bất thường (còn con hoặc còn sản phẩm
     *   gắn trực tiếp) thì giữ nguyên root rỗng và ghi log cảnh báo.
     */
    public function up(): void
    {
        $plantRoot = DB::table('categories')
            ->whereNull('parent_id')
            ->where('name', 'Kiểu loại cây')
            ->first();

        if (! $plantRoot) {
            // Không tìm thấy root nguồn -> môi trường này chưa có dữ liệu
            // tương ứng, không làm gì để tránh gây lỗi.
            return;
        }

        $groups = $this->groups();
        $newRootNames = array_keys($groups);

        $alreadyMigrated = DB::table('categories')
            ->whereNull('parent_id')
            ->whereIn('name', $newRootNames)
            ->count();

        if ($alreadyMigrated === count($newRootNames)) {
            // Cả 3 root mới đã tồn tại -> migration này đã chạy trước đó.
            return;
        }

        $now = Carbon::now();

        // Lấy toàn bộ con hiện tại của root "Kiểu loại cây" để tra cứu theo tên.
        $children = DB::table('categories')
            ->where('parent_id', $plantRoot->id)
            ->get(['id', 'name']);

        $childIdByName = [];
        foreach ($children as $child) {
            $childIdByName[$child->name] = $child->id;
        }

        foreach ($groups as $groupName => $childNames) {
            $newRootId = DB::table('categories')->insertGetId([
                'name' => $groupName,
                'scope' => 'plant',
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $childIds = [];
            foreach ($childNames as $childName) {
                if (isset($childIdByName[$childName])) {
                    $childIds[] = $childIdByName[$childName];
                }
            }

            if (! empty($childIds)) {
                DB::table('categories')
                    ->whereIn('id', $childIds)
                    ->update([
                        'parent_id' => $newRootId,
                        'updated_at' => $now,
                    ]);
            }
        }

        // Kiểm tra an toàn trước khi xoá root "Kiểu loại cây" cũ.
        $remainingChildren = DB::table('categories')->where('parent_id', $plantRoot->id)->count();
        $remainingDirectProducts = DB::table('category_product')->where('category_id', $plantRoot->id)->count();

        if ($remainingChildren === 0 && $remainingDirectProducts === 0) {
            DB::table('categories')->where('id', $plantRoot->id)->delete();
        } else {
            // Bất thường: root "Kiểu loại cây" vẫn còn con hoặc còn sản phẩm
            // gắn trực tiếp sau khi đã chuyển hết các danh mục con đã biết.
            // Không xoá root này để tránh mất dữ liệu — giữ nguyên (có thể
            // rỗng hoặc còn sót danh mục con không nằm trong danh sách map
            // ở trên) để laravel-backend / qa-tester kiểm tra lại thủ công.
            logger()->warning(
                'Migration reorganize_plant_category_tree_into_three_groups: '
                . "root 'Kiểu loại cây' (id={$plantRoot->id}) vẫn còn "
                . "{$remainingChildren} danh mục con và {$remainingDirectProducts} "
                . 'sản phẩm gắn trực tiếp sau khi tái cấu trúc, nên không bị xoá.'
            );
        }
    }

    /**
     * Reverse the migrations: gộp lại 6 danh mục con về root "Kiểu loại cây"
     * (tạo lại root này nếu đã bị xoá ở up()), rồi xoá 3 root nhóm mới.
     */
    public function down(): void
    {
        $now = Carbon::now();

        $plantRoot = DB::table('categories')
            ->whereNull('parent_id')
            ->where('name', 'Kiểu loại cây')
            ->first();

        $plantRootId = $plantRoot->id ?? null;

        if (! $plantRootId) {
            $plantRootId = DB::table('categories')->insertGetId([
                'name' => 'Kiểu loại cây',
                'scope' => 'plant',
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $groups = $this->groups();

        foreach (array_keys($groups) as $groupName) {
            $groupRoot = DB::table('categories')
                ->whereNull('parent_id')
                ->where('name', $groupName)
                ->first();

            if (! $groupRoot) {
                continue;
            }

            DB::table('categories')
                ->where('parent_id', $groupRoot->id)
                ->update([
                    'parent_id' => $plantRootId,
                    'updated_at' => $now,
                ]);

            DB::table('categories')->where('id', $groupRoot->id)->delete();
        }
    }
};
