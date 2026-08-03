@if($posts->isEmpty())
    <div class="fe-empty">{{ $empty ?? 'No feed posts found.' }}</div>
@else
    <div class="fe-posts">
        @foreach($posts as $post)
            <article class="fe-post" wire:key="feed-post-{{ $post->id }}">
                <div class="fe-thumb">
                    @if($post->thumbnail_url)
                        <img src="{{ $post->thumbnail_url }}" alt="">
                    @else
                        {{ strtoupper(substr((string) ($post->post_type ?: 'F'), 0, 1)) }}
                    @endif
                </div>
                <div>
                    <div class="fe-post-title">{{ $post->title }}</div>
                    @if($post->excerpt)
                        <div class="fe-post-excerpt">{{ \Illuminate\Support\Str::limit(strip_tags((string) $post->excerpt), 120) }}</div>
                    @endif
                    <div class="fe-meta">
                        <span class="fe-badge {{ $post->status === 'published' ? 'success' : ($post->status === 'pending' ? 'warning' : '') }}">{{ ucfirst(str_replace('_',' ', $post->status ?: 'draft')) }}</span>
                        <span class="fe-badge">{{ $this->labelFor($post->bucket, $this->bucketOptions()) }}</span>
                        <span class="fe-badge purple">{{ $this->labelFor($post->post_type, $this->postTypeOptions()) }}</span>
                        @if($post->is_pinned)<span class="fe-badge success">Pinned</span>@endif
                        @if($post->is_featured)<span class="fe-badge purple">Featured</span>@endif
                    </div>
                    <div class="fe-meta">
                        <span class="fe-badge">{{ $post->reactions_count ?? 0 }} likes</span>
                        <span class="fe-badge">{{ $post->comments_count ?? 0 }} comments</span>
                        <span class="fe-badge">{{ $post->shares_count ?? 0 }} shares</span>
                    </div>
                    <div class="fe-post-actions">
                        <a class="fe-btn small" href="{{ $this->editPostUrl($post->id) }}">Edit</a>
                        <button type="button" class="fe-btn small" wire:click="togglePin({{ $post->id }})">{{ $post->is_pinned ? 'Unpin' : 'Pin' }}</button>
                        @if($post->status !== 'published')
                            <button type="button" class="fe-btn small primary" wire:click="publishPost({{ $post->id }})">Publish</button>
                        @else
                            <button type="button" class="fe-btn small danger" wire:click="hidePost({{ $post->id }})">Hide</button>
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endif
