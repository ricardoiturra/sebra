<?php

namespace App\Providers;

use App\Models\Project;
use App\Support\Audio\CompatibleAudioProvider;
use App\Support\Audio\SpeechToText;
use App\Support\Audio\TextToSpeech;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SpeechToText::class, CompatibleAudioProvider::class);
        $this->app->bind(TextToSpeech::class, CompatibleAudioProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('audio', function (Request $request): array {
            $user = $request->user()->getAuthIdentifier();
            $project = $request->route('project');
            // Throttling can run before implicit binding and route authorization.
            if ($project !== null) {
                $project = $project instanceof Project ? $project : Project::query()->findOrFail($project);
                Gate::authorize('view', $project);
                $team = $project->team_id;
            } else {
                abort_unless($request->user()->hasRole('full admin'), 403);
                $team = 'system';
            }

            return [
                Limit::perMinute(config('audio.requests_per_minute'))->by('audio-minute:'.$user),
                Limit::perDay(config('audio.requests_per_day'))->by('audio-day:'.$user),
                Limit::perDay(config('audio.team_requests_per_day'))->by('audio-team:'.$team),
            ];
        });
    }
}
