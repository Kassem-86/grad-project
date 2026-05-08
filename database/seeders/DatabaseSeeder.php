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
        $users = User::factory(10)->create();

        // Add the main user to the collection
        $allUsers = User::all();

        // 3. For each user, create 3 posts with comments and likes
        $allUsers->each(function (User $user) use ($allUsers) {
            // Create 3 posts per user using has() relationship method
            $user->posts()->createMany(
                Post::factory(3)->make()->toArray()
            );

            // Get the posts created for this user
            $userPosts = $user->posts;

            $userPosts->each(function (Post $post) use ($allUsers) {
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
                
                // Shuffle users and take unique likers
                $likers = $allUsers->shuffle()->take($likeCount);

                $likers->each(function (User $liker) use ($post) {
                    // Check if this user already liked this post to avoid duplicates
                    if (!$post->likes()->where('user_id', $liker->id)->exists()) {
                        $post->likes()->create([
                            'user_id' => $liker->id,
                            'likeable_type' => Post::class,
                        ]);
                    }
                });

                // Update like and comment counts
                $post->update([
                    'likes_count' => $post->likes()->count(),
                    'comments_count' => $post->comments()->count(),
                ]);
            });
        });
    }
}