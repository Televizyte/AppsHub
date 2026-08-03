Dunamis TV / AppsHub global scheduling fix

Changes:
1. ContentPost model applies one central timing policy to all content_posts-backed engines.
2. Future publish_at keeps approved content hidden until due and aligns published_at to the scheduled time.
3. Automatic content pushes inherit publish_at.
4. Future pushes are saved as scheduled and are not dispatched early.
5. Editing publish_at updates an existing pending automatic push.
6. ContentPost observer reacts to publish_at changes.

Covered content_posts-backed engines include quotes, articles/highlights, short videos,
daily content, motivation, Wordification, SOD, and other dynamic content buckets.
Books and quizzes do not currently expose a publish_at column in the inspected schema;
their existing immediate-publish behavior is preserved.
