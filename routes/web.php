<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\NewsClusterController;
use App\Http\Controllers\SourceController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\UserTopicPreferenceController;
use App\Livewire\Radar;
use Illuminate\Support\Facades\Route;

Route::get('/', HomepageController::class)->name('home');
Route::get('/news/{newsCluster:slug}', [NewsClusterController::class, 'show'])->name('news.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/radar', Radar::class)->name('radar');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
    // Kept as a compatibility URL for links created before the public news URL.
    Route::get('/news-clusters/{newsCluster:slug}', [NewsClusterController::class, 'show'])->name('news-clusters.show');
    Route::get('/my-topics', [UserTopicPreferenceController::class, 'index'])->name('my-topics.index');
    Route::post('/my-topics/{topic}/follow', [UserTopicPreferenceController::class, 'follow'])->name('my-topics.follow');
    Route::post('/my-topics/{topic}/exclude', [UserTopicPreferenceController::class, 'exclude'])->name('my-topics.exclude');
    Route::patch('/my-topics/{topic}', [UserTopicPreferenceController::class, 'update'])->name('my-topics.update');
    Route::delete('/my-topics/{topic}', [UserTopicPreferenceController::class, 'destroy'])->name('my-topics.destroy');
    Route::post('/my-topics/suggestions/{suggestion}/ignore', [UserTopicPreferenceController::class, 'ignoreSuggestion'])->name('my-topics.suggestions.ignore');
    Route::patch('/topics/{topic}/approve', [TopicController::class, 'approve'])->name('topics.approve');
    Route::patch('/topics/{topic}/ignore', [TopicController::class, 'ignore'])->name('topics.ignore');
    Route::resource('topics', TopicController::class)->except(['show']);

    Route::get('/sources/search', [SourceController::class, 'search'])->name('sources.search');
    Route::resource('sources', SourceController::class)->except(['show']);
    Route::resource('sources.feeds', FeedController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy']);
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/observability', [AdminController::class, 'index'])->name('observability');
    Route::post('/articles/{article}/reprocess', [AdminController::class, 'reprocessArticle'])->name('articles.reprocess');
    Route::post('/feeds/{feed}/retry', [AdminController::class, 'retryFeed'])->name('feeds.retry');
    Route::post('/news-clusters/{newsCluster}/recalculate', [AdminController::class, 'recalculateCluster'])->name('news-clusters.recalculate');
});
