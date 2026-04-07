<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Like;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
  public function run(): void
    {
        // 1. كرييت يوزر أساسي ليك (Ziad)
        $me = User::factory()->create([
            'first_name' => 'Ziad',
            'last_name' => 'Kassem',
            'email' => 'ziad@example.com',
            'password' => Hash::make('12345678'),
        ]);

        // 2. كرييت 10 يوزرز كمان
        User::factory(10)->create();

        // نجمع كل اليوزرز في Collection واحدة
        $allUsers = User::all();

        // 3. لكل يوزر (بما فيهم إنت)، هنعمل 3 بوستات
        $allUsers->each(function (User $user) use ($allUsers) {
            $posts = Post::factory(3)->create(['user_id' => $user->id]);

            // 4. لكل بوست، هنعمل كومنتات ولايكات
            $posts->each(function (Post $post) use ($allUsers) {
                
                // عمل 5 كومنتات عشوائية
                Comment::factory(5)->create([
                    'post_id' => $post->id,
                    'user_id' => $allUsers->random()->id,
                ]);

                // --- الحل الأكيد لمشكلة الـ Duplicate Entry ---
                // بنلخبط اليوزرز وناخد أول 5 "فريدين" يعملوا لايك للبوست ده
                $randomLikers = $allUsers->shuffle()->take(5); 

                foreach ($randomLikers as $liker) {
                    $post->likes()->create([
                        'user_id' => $liker->id,
                    ]);
                }
            });
        });
    }
}