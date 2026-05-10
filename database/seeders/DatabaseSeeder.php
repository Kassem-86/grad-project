<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Like;
use App\Models\PostImage; // تأكد إن الموديل ده موجود
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create a main user (Ziad)
        $me = User::where('email', 'ziad@example.com')->first();
        if (!$me) {
            $me = User::factory()->create([
                'first_name' => 'Ziad',
                'last_name' => 'Kassem',
                'email' => 'ziad@example.com',
                'password' => Hash::make('12345678'),
            ]);
        }

        // 2. Create 10 additional users
        User::factory(10)->create();

        // Get all users to use in relationships
        $allUsers = User::all();

        // 3. For each user, create 3 posts with images, comments, and likes
        $allUsers->each(function (User $user) use ($allUsers) {
            
            // Create 3 posts per user
            $user->posts()->createMany(
                Post::factory(3)->make()->toArray()
            );

            // Get the posts created for this user
            $userPosts = $user->posts;

            $userPosts->each(function (Post $post) use ($allUsers, $user) {
                
                // 4a. Add 3 to 5 random comments per post
                $commentCount = fake()->numberBetween(3, 5);
                for ($i = 0; $i < $commentCount; $i++) {
                    $randomUser = $allUsers->random();
                    $post->comments()->create([
                        'user_id' => $randomUser->id,
                        'comment_text' => Comment::factory()->make()->comment_text,
                    ]);
                }

                // 4b. Add 5 to 10 likes from different users (ensure uniqueness)
                $likeCount = fake()->numberBetween(5, 10);
                $likers = $allUsers->shuffle()->take($likeCount);

                $likers->each(function (User $liker) use ($post) {
                    if (!$post->likes()->where('user_id', $liker->id)->exists()) {
                        $post->likes()->create([
                            'user_id' => $liker->id,
                            'likeable_type' => Post::class,
                        ]);
                    }
                });

                // 4c. [NEW] Add 1 to 3 images for each post
                $imageCount = fake()->numberBetween(1, 3);
                for ($i = 0; $i < $imageCount; $i++) {
                    $post->images()->create([
                        'user_id' => $user->id, // صاحب البوست هو اللي رفع الصورة
                        'image_path' => 'posts/demo_img_' . fake()->numberBetween(1, 10) . '.jpg',
                    ]);
                }

                // Update counts in the community table
                $post->update([
                    'likes_count' => $post->likes()->count(),
                    'comments_count' => $post->comments()->count(),
                ]);
            });
        });

        // 5. Seed logs and health tracking data
        $this->call(LogSeeder::class);
    }
}