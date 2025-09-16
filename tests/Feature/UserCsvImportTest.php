<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UserCsvImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 管理者ユーザーを作成してログイン
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $this->actingAs($this->adminUser);
    }

    public function test_admin_can_download_csv_template()
    {
        $response = $this->get(route('master.users.template-csv'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="users_template_'.now()->format('Ymd_His').'.csv"');
    }

    public function test_admin_can_export_csv()
    {
        $response = $this->get(route('master.users.export-csv'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_can_import_valid_csv()
    {
        $csvContent = implode("\n", [
            'sort,name,furigana,email,role,affiliation,hired_at,birthday,mobile_phone,postal_code,address,emergency_contact_name,emergency_contact_phone,notes,is_staff,is_designer,is_driver,is_on_leave,is_resigned,is_active',
            '1,"田中太郎","たなかたろう","tanaka@example.com","editor","employee","2023-04-01","1990-01-01","090-1234-5678","123-4567","東京都渋谷区","田中花子","090-8765-4321","備考なし","1","0","0","0","0","1"',
        ]);

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->post(route('master.users.import-csv'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => '田中太郎',
            'email' => 'tanaka@example.com',
            'role' => 'editor',
            'affiliation' => 'employee',
            'is_staff' => true,
            'is_designer' => false,
            'is_driver' => false,
        ]);
    }

    public function test_csv_import_requires_valid_file()
    {
        $response = $this->post(route('master.users.import-csv'), []);

        $response->assertSessionHasErrors('csv_file');
    }

    public function test_csv_import_validates_required_fields()
    {
        // 必須項目（name）が不足しているCSV
        $csvContent = implode("\n", [
            'sort,name,furigana,email,role,affiliation,hired_at,birthday,mobile_phone,postal_code,address,emergency_contact_name,emergency_contact_phone,notes,is_staff,is_designer,is_driver,is_on_leave,is_resigned,is_active',
            '1,"","たなかたろう","tanaka@example.com","editor","employee","2023-04-01","1990-01-01","090-1234-5678","123-4567","東京都渋谷区","田中花子","090-8765-4321","備考なし","1","0","0","0","0","1"',
        ]);

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->post(route('master.users.import-csv'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_csv_import_validates_email_format()
    {
        // 不正なメールアドレス形式のCSV
        $csvContent = implode("\n", [
            'ソート順,氏名,ふりがな,メールアドレス,権限,所属,部署,役職,入社日,生年月日,電話番号,携帯電話,郵便番号,住所,緊急連絡先氏名,緊急連絡先電話,備考,スタッフ,デザイナー,ドライバー,休職中,退職済,アクティブ',
            '1,"田中太郎","たなかたろう","invalid-email","editor","employee","開発部","エンジニア","2023-04-01","1990-01-01","03-1234-5678","090-1234-5678","123-4567","東京都渋谷区","田中花子","090-8765-4321","備考なし","1","0","0","0","0","1"',
        ]);

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->post(route('master.users.import-csv'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_csv_import_validates_role_values()
    {
        // 不正な権限値のCSV
        $csvContent = implode("\n", [
            'ソート順,氏名,ふりがな,メールアドレス,権限,所属,部署,役職,入社日,生年月日,電話番号,携帯電話,郵便番号,住所,緊急連絡先氏名,緊急連絡先電話,備考,スタッフ,デザイナー,ドライバー,休職中,退職済,アクティブ',
            '1,"田中太郎","たなかたろう","tanaka@example.com","invalid_role","employee","開発部","エンジニア","2023-04-01","1990-01-01","03-1234-5678","090-1234-5678","123-4567","東京都渋谷区","田中花子","090-8765-4321","備考なし","1","0","0","0","0","1"',
        ]);

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->post(route('master.users.import-csv'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_csv_import_updates_existing_user_by_email()
    {
        // 既存ユーザーを作成
        $existingUser = User::factory()->create([
            'name' => '旧名前',
            'email' => 'tanaka@example.com',
            'role' => 'viewer',
        ]);

        $csvContent = implode("\n", [
            'ソート順,氏名,ふりがな,メールアドレス,権限,所属,部署,役職,入社日,生年月日,電話番号,携帯電話,郵便番号,住所,緊急連絡先氏名,緊急連絡先電話,備考,スタッフ,デザイナー,ドライバー,休職中,退職済,アクティブ',
            '1,"田中太郎","たなかたろう","tanaka@example.com","editor","employee","開発部","エンジニア","2023-04-01","1990-01-01","03-1234-5678","090-1234-5678","123-4567","東京都渋谷区","田中花子","090-8765-4321","備考なし","1","0","0","0","0","1"',
        ]);

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->post(route('master.users.import-csv'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // 既存ユーザーが更新されたことを確認
        $existingUser->refresh();
        $this->assertEquals('田中太郎', $existingUser->name);
        $this->assertEquals('editor', $existingUser->role);
        $this->assertEquals('employee', $existingUser->affiliation);
    }

    public function test_non_admin_cannot_import_csv()
    {
        // 編集者ユーザーでログイン
        $editorUser = User::factory()->create([
            'role' => 'editor',
            'email' => 'editor@example.com',
        ]);

        $this->actingAs($editorUser);

        $csvContent = 'test,data';
        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->post(route('master.users.import-csv'), [
            'csv_file' => $file,
        ]);

        $response->assertStatus(403);
    }

    public function test_csv_import_handles_japanese_role_labels()
    {
        $csvContent = implode("\n", [
            'ソート順,氏名,ふりがな,メールアドレス,権限,所属,部署,役職,入社日,生年月日,電話番号,携帯電話,郵便番号,住所,緊急連絡先氏名,緊急連絡先電話,備考,スタッフ,デザイナー,ドライバー,休職中,退職済,アクティブ',
            '1,"田中太郎","たなかたろう","tanaka@example.com","管理者","社員","開発部","エンジニア","2023-04-01","1990-01-01","03-1234-5678","090-1234-5678","123-4567","東京都渋谷区","田中花子","090-8765-4321","備考なし","1","0","0","0","0","1"',
        ]);

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->post(route('master.users.import-csv'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => '田中太郎',
            'email' => 'tanaka@example.com',
            'role' => 'admin', // adminが正しく設定されることを確認
            'affiliation' => 'employee', // employeeが正しく設定されることを確認
        ]);
    }

    public function test_csv_import_handles_boolean_values()
    {
        $csvContent = implode("\n", [
            'ソート順,氏名,ふりがな,メールアドレス,権限,所属,部署,役職,入社日,生年月日,電話番号,携帯電話,郵便番号,住所,緊急連絡先氏名,緊急連絡先電話,備考,スタッフ,デザイナー,ドライバー,休職中,退職済,アクティブ',
            '1,"田中太郎","たなかたろう","tanaka@example.com","editor","employee","開発部","エンジニア","2023-04-01","1990-01-01","03-1234-5678","090-1234-5678","123-4567","東京都渋谷区","田中花子","090-8765-4321","備考なし","はい","Yes","true","0","false","1"',
        ]);

        $file = UploadedFile::fake()->createWithContent('test.csv', $csvContent);

        $response = $this->post(route('master.users.import-csv'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => '田中太郎',
            'email' => 'tanaka@example.com',
            'is_staff' => true,      // 「はい」 -> true
            'is_designer' => true,   // 「Yes」 -> true
            'is_driver' => true,     // 「true」 -> true
            'is_on_leave' => false,  // 「0」 -> false
            'is_resigned' => false,  // 「false」 -> false
            'is_active' => true,     // 「1」 -> true
        ]);
    }
}
