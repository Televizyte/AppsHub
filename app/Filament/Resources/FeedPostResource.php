<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Base\ActiveAppScopedResource;
use App\Filament\Resources\FeedPostResource\Pages;
use App\Models\FeedPost;
use App\Support\ActiveApp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FeedPostResource extends ActiveAppScopedResource
{

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationItems(): array
    {
        return [];
    }

    protected static ?string $model = FeedPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Engagement';
    protected static ?string $navigationLabel = 'Community Feed';
    protected static ?string $modelLabel = 'Feed Post';
    protected static ?string $pluralModelLabel = 'Community Feed';
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('app_id')
                ->default(fn () => (int) ActiveApp::ensureId())
                ->dehydrated(),

            Forms\Components\Hidden::make('created_by_type')
                ->default('admin')
                ->dehydrated(),

            Forms\Components\Hidden::make('created_by_id')
                ->dehydrated(),

            Forms\Components\Section::make('Create a Community Feed Post')
                ->description('Use this guided workspace to publish controlled community updates for the active app. Technical feed fields are kept under Advanced Options.')
                ->columns(12)
                ->schema([
                    Forms\Components\Select::make('post_type')
                        ->label('What type of post is this?')
                        ->helperText('Choose the kind of card the app should display in the community feed.')
                        ->options(self::postTypeOptions())
                        ->default('announcement')
                        ->required()
                        ->searchable()
                        ->columnSpan(4),

                    Forms\Components\Select::make('bucket')
                        ->label('Where should it appear?')
                        ->helperText('This controls the feed filter/category, such as Announcements, Articles, Quotes, or Short Videos.')
                        ->options(self::bucketOptions())
                        ->default('announcements')
                        ->required()
                        ->searchable()
                        ->columnSpan(4),

                    Forms\Components\Select::make('status')
                        ->label('Publishing status')
                        ->helperText('Use Draft while preparing, or Publish Now when ready for the app feed.')
                        ->options([
                            'draft' => 'Draft - keep hidden for now',
                            'published' => 'Publish Now',
                            'pending' => 'Pending Review',
                            'hidden' => 'Hidden',
                            'archived' => 'Archived',
                        ])
                        ->default('draft')
                        ->required()
                        ->columnSpan(4),

                    Forms\Components\Toggle::make('is_pinned')
                        ->label('Pin this post to the top')
                        ->helperText('Use for live service, urgent announcements, or important updates.')
                        ->default(false)
                        ->columnSpan(6),

                    Forms\Components\Toggle::make('is_featured')
                        ->label('Mark as featured')
                        ->helperText('Featured posts can be highlighted in feed previews later.')
                        ->default(false)
                        ->columnSpan(6),
                ]),

            Forms\Components\Section::make('Write the Post')
                ->description('Add the title, short summary, and main post message users will see in the feed.')
                ->columns(12)
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Post Title')
                        ->placeholder('Example: Welcome to the Dunamis TV Community Feed')
                        ->maxLength(255)
                        ->required()
                        ->columnSpan(8),

                    Forms\Components\TextInput::make('cta_label')
                        ->label('Button Text')
                        ->placeholder('Open / Read Article / Watch Now')
                        ->maxLength(80)
                        ->columnSpan(4),

                    Forms\Components\Textarea::make('excerpt')
                        ->label('Short Summary')
                        ->placeholder('A brief summary that appears under the title.')
                        ->rows(3)
                        ->maxLength(500)
                        ->columnSpan(12),

                    Forms\Components\RichEditor::make('body')
                        ->label('Main Message')
                        ->toolbarButtons([
                            'bold', 'italic', 'bulletList', 'orderedList', 'link', 'undo', 'redo',
                        ])
                        ->columnSpan(12),
                ]),

            Forms\Components\Section::make('Media and App Link')
                ->description('Optional: add an image and connect this post back to the original app content, such as an article, short video, quote, book, quiz, or live broadcast.')
                ->columns(12)
                ->schema([
                    Forms\Components\TextInput::make('thumbnail_url')
                        ->label('Thumbnail Image URL')
                        ->placeholder('Paste image URL or media library URL')
                        ->maxLength(2048)
                        ->columnSpan(6),

                    Forms\Components\TextInput::make('media_url')
                        ->label('Media URL')
                        ->placeholder('Optional video/audio/image URL')
                        ->maxLength(2048)
                        ->columnSpan(6),

                    Forms\Components\Select::make('source_engine')
                        ->label('Open content from')
                        ->helperText('Select the engine this post should open. Leave blank for a simple announcement.')
                        ->options(self::sourceEngineOptions())
                        ->searchable()
                        ->placeholder('No linked content / announcement only')
                        ->columnSpan(4),

                    Forms\Components\TextInput::make('source_id')
                        ->label('Content ID or Slug')
                        ->helperText('Example: article ID, short video ID, book ID, quiz slug, or live key.')
                        ->maxLength(120)
                        ->columnSpan(4),

                    Forms\Components\TextInput::make('deep_link')
                        ->label('App Action / Deep Link')
                        ->placeholder('article:25, short_video:15, watch:live')
                        ->helperText('Used by the mobile app to open the correct screen.')
                        ->maxLength(255)
                        ->columnSpan(4),
                ]),

            Forms\Components\Section::make('Advanced Options')
                ->description('Internal controls for visibility, approval, scheduling, ranking, and metadata. Most beginner users can leave these unchanged.')
                ->collapsible()
                ->collapsed()
                ->columns(12)
                ->schema([
                    Forms\Components\Select::make('approval_status')
                        ->label('Approval Status')
                        ->options([
                            'approved' => 'Approved',
                            'pending' => 'Pending',
                            'rejected' => 'Rejected',
                        ])
                        ->default('approved')
                        ->required()
                        ->columnSpan(3),

                    Forms\Components\Select::make('visibility')
                        ->label('Who can see it?')
                        ->options([
                            'public' => 'Public',
                            'app_users' => 'Signed-in App Users',
                            'admin_only' => 'Admin Only',
                        ])
                        ->default('public')
                        ->required()
                        ->columnSpan(3),

                    Forms\Components\DateTimePicker::make('published_at')
                        ->label('Publish Time')
                        ->seconds(false)
                        ->default(now())
                        ->columnSpan(3),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Priority Order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Higher number appears earlier among similar posts.')
                        ->columnSpan(3),

                    Forms\Components\KeyValue::make('meta_json')
                        ->label('Extra Metadata')
                        ->keyLabel('Key')
                        ->valueLabel('Value')
                        ->columnSpan(12),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Community Feed Library')
            ->description('Manage app-specific feed posts, pinned updates, announcements, articles, quotes, short videos, achievements, and user activity posts.')
            ->columns([
                Tables\Columns\IconColumn::make('is_pinned')
                    ->label('Pinned')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('title')
                    ->label('Post')
                    ->description(fn (FeedPost $record): ?string => $record->excerpt ? str($record->excerpt)->stripTags()->limit(80)->toString() : null)
                    ->searchable()
                    ->limit(55)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('bucket')
                    ->label('Feed Area')
                    ->formatStateUsing(fn (?string $state): string => self::bucketOptions()[$state] ?? str((string) $state)->replace('_', ' ')->title()->toString())
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('post_type')
                    ->label('Type')
                    ->formatStateUsing(fn (?string $state): string => self::postTypeOptions()[$state] ?? str((string) $state)->replace('_', ' ')->title()->toString())
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'published' => 'Published',
                        'pending' => 'Pending Review',
                        'hidden' => 'Hidden',
                        'archived' => 'Archived',
                        default => 'Draft',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'published' => 'success',
                        'pending' => 'warning',
                        'hidden', 'archived' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('approval_status')
                    ->label('Approval')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'approved' => 'Approved',
                        'pending' => 'Pending',
                        'rejected' => 'Rejected',
                        default => 'Pending',
                    })
                    ->color(fn (?string $state): string => $state === 'approved' ? 'success' : ($state === 'pending' ? 'warning' : 'danger')),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Publish Time')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bucket')
                    ->label('Feed Area')
                    ->options(self::bucketOptions()),
                Tables\Filters\SelectFilter::make('post_type')
                    ->label('Post Type')
                    ->options(self::postTypeOptions()),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Publishing Status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending Review',
                        'published' => 'Published',
                        'hidden' => 'Hidden',
                        'archived' => 'Archived',
                    ]),
                Tables\Filters\Filter::make('pinned')
                    ->label('Pinned Only')
                    ->query(fn (Builder $query): Builder => $query->where('is_pinned', true)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit'),
                Tables\Actions\Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (FeedPost $record): bool => $record->status !== 'published')
                    ->action(function (FeedPost $record): void {
                        $record->update([
                            'status' => 'published',
                            'approval_status' => 'approved',
                            'published_at' => $record->published_at ?: now(),
                        ]);
                    }),
                Tables\Actions\Action::make('hide')
                    ->label('Hide')
                    ->icon('heroicon-o-eye-slash')
                    ->color('danger')
                    ->visible(fn (FeedPost $record): bool => $record->status === 'published')
                    ->requiresConfirmation()
                    ->action(fn (FeedPost $record) => $record->update(['status' => 'hidden'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No community posts yet')
            ->emptyStateDescription('Create your first community update for the active app. You can publish announcements, articles, short videos, quotes, live reminders, testimonies, and achievement posts.')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right')
            ->defaultSort('published_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeedPosts::route('/'),
            'create' => Pages\CreateFeedPost::route('/create'),
            'edit' => Pages\EditFeedPost::route('/{record}/edit'),
        ];
    }

    public static function bucketOptions(): array
    {
        return [
            'admin_posts' => 'Admin Posts',
            'announcements' => 'Announcements',
            'articles' => 'Articles',
            'quotes' => 'Quotes',
            'short_videos' => 'Short Videos',
            'videos' => 'Videos',
            'books' => 'Books',
            'daily_scripture' => 'Daily Scripture',
            'bible_quiz' => 'Bible Quiz',
            'game_achievements' => 'Game Achievements',
            'live_broadcast' => 'Live Broadcast',
            'events' => 'Events',
            'testimonies' => 'Testimonies',
            'devotional' => 'Devotional',
            'user_activity' => 'User Activity',
        ];
    }

    public static function postTypeOptions(): array
    {
        return [
            'text_post' => 'Text Post',
            'announcement' => 'Announcement',
            'article' => 'Article',
            'short_video' => 'Short Video',
            'quote' => 'Quote',
            'daily_scripture' => 'Daily Scripture',
            'book' => 'Book',
            'live' => 'Live Broadcast',
            'event' => 'Event',
            'quiz_result' => 'Quiz Result',
            'game_achievement' => 'Game Achievement',
            'user_reflection' => 'User Reflection',
            'testimony' => 'Testimony',
        ];
    }

    public static function sourceEngineOptions(): array
    {
        return [
            'content' => 'Article / Content Publisher',
            'short_video' => 'Short Video Engine',
            'quote' => 'Quote Engine',
            'quote_creator' => 'Quote Creator',
            'book' => 'Book Engine',
            'bible' => 'Bible / Scripture',
            'quiz' => 'Quiz Engine',
            'game' => 'Game Engine',
            'watch' => 'Watch / Live Broadcast',
            'event' => 'Event',
            'admin' => 'Admin/System',
            'user_activity' => 'User Activity',
        ];
    }
}
