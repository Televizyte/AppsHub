<?php

namespace Tests\Feature;

use App\Models\App;
use App\Models\ContentPost;
use App\Models\ContentPostSave;
use App\Models\User;
use App\Services\Accounts\DeleteAppUserAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteAppUserAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_content_saves_for_the_selected_app(): void
    {
        $app = $this->createApp('App A');
        $user = User::factory()->create();
        $save = $this->createSave($app, $this->createPost($app, 'App A post'), $user);
        $saveId = $save->id;

        $result = app(DeleteAppUserAccount::class)->handle($user, $app);

        $this->assertDatabaseMissing('content_post_saves', ['id' => $saveId]);
        $this->assertSame(1, $result['deleted_records']['content_post_saves']);
    }

    public function test_it_preserves_cross_app_saves_and_the_shared_user(): void
    {
        $appA = $this->createApp('App A');
        $appB = $this->createApp('App B');
        $user = User::factory()->create();
        $saveA = $this->createSave($appA, $this->createPost($appA, 'App A post'), $user);
        $saveB = $this->createSave($appB, $this->createPost($appB, 'App B post'), $user);
        $saveAId = $saveA->id;
        $saveBId = $saveB->id;

        $result = app(DeleteAppUserAccount::class)->handle($user, $appA);

        $this->assertDatabaseMissing('content_post_saves', ['id' => $saveAId]);
        $this->assertDatabaseHas('content_post_saves', ['id' => $saveBId]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertFalse($result['user_deleted']);
    }

    public function test_it_deletes_the_shared_user_when_no_other_app_data_or_tokens_remain(): void
    {
        $app = $this->createApp('App A');
        $user = User::factory()->create();
        $this->createSave($app, $this->createPost($app, 'App A post'), $user);

        $result = app(DeleteAppUserAccount::class)->handle($user, $app);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertTrue($result['user_deleted']);
    }

    public function test_it_removes_only_selected_app_tokens_and_retains_the_shared_user(): void
    {
        $appA = $this->createApp('App A');
        $appB = $this->createApp('App B');
        $user = User::factory()->create();
        $appAToken = $user->createToken('app:'.$appA->slug, ['app:'.$appA->id, 'app_slug:'.$appA->slug])->accessToken;
        $appBToken = $user->createToken('app:'.$appB->slug, ['app:'.$appB->id, 'app_slug:'.$appB->slug])->accessToken;

        $result = app(DeleteAppUserAccount::class)->handle($user, $appA);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $appAToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $appBToken->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertFalse($result['user_deleted']);
    }

    private function createApp(string $name): App
    {
        return App::query()->create([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'api_token' => str()->random(64),
            'is_active' => true,
        ]);
    }

    private function createPost(App $app, string $title): ContentPost
    {
        return ContentPost::query()->create([
            'app_id' => $app->id,
            'bucket' => 'articles',
            'status' => 'published',
            'title' => $title,
        ]);
    }

    private function createSave(App $app, ContentPost $post, User $user): ContentPostSave
    {
        return ContentPostSave::query()->create([
            'app_id' => $app->id,
            'content_post_id' => $post->id,
            'user_id' => $user->id,
        ]);
    }
}
