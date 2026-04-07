<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            // بيختار يوزر موجود فعلاً
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            
            'title' => fake()->sentence(6),
            'content' => fake()->paragraphs(3, true),
            
            // هنا الحل: لازم نستخدم نفس الكلمات والـ Capitalization اللي في المايجريشن
            'category' => fake()->randomElement([
                'General', 
                'Type1 and LADA', 
                'Type2', 
                'gestational', 
                'advices'
            ]),
            
            'likes_count' => 0,
            'comments_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}