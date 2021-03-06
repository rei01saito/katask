<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Task;
use App\Models\Priority;
use App\Models\Target;
use App\Models\User;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;
    private $tasks_id;

    public function setUp(): void
    {
        parent::setUp();

        // User作成と認証
        $user = User::factory()->create();
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $this->assertAuthenticated();
        
        // Target作成
        $targets_id = Target::factory()->create([
            'users_id' => $user->id
        ]);
        Target::factory(4)->create([
            'users_id' => $user->id,
            'type' => 1
        ]);

        // Priority作成
        Priority::upsert([
            ['priorities_id' => 0, 'name' => '検討中'],
            ['priorities_id' => 1, 'name' => '実行中'],
            ['priorities_id' => 2, 'name' => '完了'],
        ], ['priorities_id']);

        // Task作成
        $task = Task::factory()->create([
            'targets_id' => $targets_id
        ]);
        $this->tasks_id = $task->id;
    }

    public function test_request_to_index()
    {
        $response = $this->get('/tasks');
        $response->assertOk();
    }

    public function test_request_to_store()
    {
        $response = $this->post('/tasks/store', [
            'title' => 'Is this success?',
            'contents' => 'This is a test.',
            'timer' => 50,
            'priority' => rand(0, 2)
        ]);
        $response->assertRedirect('/tasks');
        $this->assertDatabaseHas('tasks',[
            'title' => 'Is this success?',
            'contents' => 'This is a test.'
        ]);
    }

    public function test_validate_to_store()
    {
        $response = $this->post('/tasks/store', [
            'title' => '',
            'contents' => '123456789' * 25 + '123456',
            'timer' => '',
            'priority' => rand(0, 2)
        ]);
        $response->assertRedirect('/tasks');
        $this->assertDatabaseMissing('tasks',[
            'title' => '',
            'contents' => '123456789' * 25 + '123456',
            'timer' => '',
        ]);
    }

    public function test_request_to_softDelete()
    {
        $response = $this->post('/tasks/softDelete/'.$this->tasks_id);
        $response->assertJson([
            'message' => '削除しました。'
        ]);
    }

    public function test_request_to_update()
    {
        $response = $this->post('/tasks'.'/'.$this->tasks_id, [
            'title' => 'This title is changed',
            'contents' => 'This contents is changed',
            'timer' => 140
        ]);
        $response->assertRedirect('/tasks');
        $this->assertDatabaseHas('tasks',[
            'title' => 'This title is changed',
            'contents' => 'This contents is changed',
            'timer' => 140
        ]);
    }

    public function test_validate_to_update()
    {
        $response = $this->post('/tasks'.'/'.$this->tasks_id, [
            'title' => '',
            'contents' => '123456789' * 25 + '123456',
            'timer' => 'あいうえお'
        ]);
        $response->assertRedirect('/tasks');
        $this->assertDatabaseMissing('tasks',[
            'title' => '',
            'contents' => '123456789' * 25 + '123456',
            'timer' => 'あいうえお'
        ]);
    }

    public function test_request_to_trashcan()
    {
        $response = $this->get('/tasks/trashcan');
        $response->assertOk();
    }

    public function test_request_to_resotre()
    {
        // データを論理削除
        Task::where('tasks_id', $this->tasks_id)->delete();
        $trashed_task = Task::onlyTrashed()->get();
        foreach ($trashed_task as $t) {
            $this->assertNotNull($t->deleted_at);
        }

        // ゴミ箱のデータを元に戻す
        $response = $this->get('/tasks/restore');
        $restored_task = Task::where('tasks_id', $this->tasks_id)->get();
        foreach ($restored_task as $r) {
            $this->assertSame($r->deleted_at, null);
        }
        $response->assertRedirect('/tasks');
    }

    public function test_request_to_forceDelete()
    {
        // データを論理削除
        $task = Task::where('tasks_id', $this->tasks_id)->delete();
        $trashed_task = Task::onlyTrashed()->get();
        foreach ($trashed_task as $t) {
            $this->assertNotNull($t->deleted_at);
        }

        // ゴミ箱の中身を削除
        $response = $this->get('/tasks/forceDelete');
        foreach ($trashed_task as $t) {
            $this->assertDatabaseMissing('tasks', [
                'tasks_id' => $t->tasks_id
            ]);
        }
    }

    public function test_request_to_drag_update()
    {
        // 現在のpriority値
        $p = '';
        $task = Task::where('tasks_id', $this->tasks_id)->get();
        foreach ($task as $t) {
            $p = $t->priority;
        }
        
        $p_array = [0, 1, 2];
        $key = array_search($p, $p_array);
        unset($p_array[$key]);
        sort($p_array);

        $response = $this->get('/tasks/update/'.$this->tasks_id.'/'.$p_array[rand(0, 1)]);
        
        // 更新後のpriority値
        $updated_p = '';
        $task = Task::where('tasks_id', $this->tasks_id)->get();
        foreach ($task as $t) {
            $updated_p = $t->priority;
        }

        // 更新の前と後で値が変わっていたらtrue
        $bool = $p <> $updated_p;
        $this->assertTrue($bool);
    }

}
