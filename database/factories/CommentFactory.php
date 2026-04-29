<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Health-related comment content
        $commentTexts = [
            'This is so helpful! Thank you for sharing your experience.',
            'I\'ve been going through something similar. Your tips really helped!',
            'Great post! I\'ll definitely try this approach.',
            'How long did it take to see results? I\'m curious to know.',
            'This resonates with me so much. Keep up the great work!',
            'Has anyone else had similar results? Would love to hear more.',
            'Thanks for being so open about your journey. It\'s inspiring!',
            'I\'m going to share this with my doctor at my next appointment.',
            'The consistency you\'re showing is amazing. Very motivating!',
            'This is exactly what I needed to hear today. Thank you!',
            'Love your positive attitude. You\'re an inspiration to us all!',
            'Great advice! Can\'t wait to implement this in my routine.',
            'I\'m bookmarking this for later. Really valuable information.',
            'Your transparency means a lot to the community.',
            'This post came at the perfect time for me. Thank you for sharing!',
        ];

        return [
            'post_id' => Post::factory(),
            'user_id' => User::factory(),
            'comment_text' => fake()->randomElement($commentTexts),
            'likes_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
