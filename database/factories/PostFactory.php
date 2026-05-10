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
        // Health-related post titles
        $healthTitles = [
            'My latest glucose readings this week',
            'Tips for managing blood sugar levels',
            'Healthy meal prep for diabetes management',
            'Exercise routine that helps with my health',
            'New medication update and how it\'s working',
            'Sharing my health journey and progress',
            'Questions about my recent lab results',
            'Looking for advice on diet changes',
            'Celebrating my health milestones today',
            'How I manage stress and blood sugar',
            'Tips for staying motivated with health goals',
            'My experience with the new treatment',
            'Meal planning strategies that work for me',
            'Tracking progress and staying accountable',
            'Community support has been amazing',
        ];

        // Health-related content
        $healthContent = [
            'Just checked my blood sugar and it\'s looking great! I\'ve been following a consistent exercise routine and eating better. Feeling more energetic than ever!',
            'Started a new meal plan this week and I\'m really impressed with the results. My energy levels are much more stable throughout the day.',
            'Has anyone tried this new approach to managing their condition? I\'d love to hear about your experiences!',
            'Today marks 30 days of consistent tracking. It\'s amazing to see the patterns and how my lifestyle choices affect my health.',
            'My doctor adjusted my medication yesterday and I\'m already noticing positive changes. Really excited about this next phase!',
            'Meal prepping on Sundays has been a game-changer for me. I can stay on track even when life gets busy.',
            'Just finished an amazing workout! Feeling stronger every day and my health metrics are improving.',
            'Want to share some resources I found helpful on my health journey. Happy to discuss in the comments!',
            'Having a tough day with motivation, but remembering why I started helps me push through.',
            'The support from this community means everything. Thank you all for the encouragement and advice!',
        ];

        return [
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
            
            'title' => fake()->randomElement($healthTitles),
            'content' => fake()->randomElement($healthContent),
            
            'category' => fake()->randomElement([
                'General', 
                'Type 1 and LADA',
                'Type 2', 
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