-- =========================================================
-- MoraConnect — sample technical articles
--
-- Gives the platform realistic content to display. Safe to
-- re-run: it clears the seeded articles first, then reinserts.
--
-- HOW TO USE:
--   phpMyAdmin -> moraconnect -> Import -> choose this file
--   or: mysql -u root moraconnect < seed.sql
--
-- This only touches articles written by the three sample
-- accounts (alex_chen, priya_patel, pasindu). Articles by any
-- other account are left alone.
-- =========================================================

USE moraconnect;

DELETE FROM blogPost
WHERE user_id IN (SELECT id FROM users WHERE username IN ('alex_chen', 'priya_patel', 'pasindu'));

INSERT INTO blogPost (user_id, title, content, category, created_at) VALUES

((SELECT id FROM users WHERE username = 'alex_chen'),
 'Why your PHP app should have exactly one entry point',
 'For the first two years of writing PHP I shipped one file per page. login.php, dashboard.php, delete-user.php. It works, and for a small site it works for a long time.\n\nThe problem is not the files. The problem is that every one of them is a door into your application, and every door has to be locked separately. Add session handling to one and you have to remember it in the other eleven. Add a CSRF check and you will miss one.\n\nA front controller flips this around. Apache rewrites every request that is not a real file to a single index.php, and that file becomes the only way in. Now there is exactly one place to start the session, one place to load configuration, one place to decide what runs.\n\nThe rewrite rule is three lines:\n\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule ^(.*)$ index.php?url=$1 [QSA,L]\n\nThe two conditions matter. Without them your CSS and images get routed through PHP too, which is both slow and confusing to debug.\n\nWhat surprised me was not the tidiness. It was that cross-cutting concerns stopped being a discipline problem and became a structural one. You cannot forget to check a token when there is only one function that dispatches requests.',
 'Web Development', NOW() - INTERVAL 2 DAY),

((SELECT id FROM users WHERE username = 'priya_patel'),
 'CSS custom properties replaced my design system spreadsheet',
 'I used to keep a spreadsheet of hex codes. Every time a colour changed I would search the stylesheet, find fourteen occurrences, miss two, and ship a button that was subtly the wrong shade of green.\n\nCustom properties fix this properly, not cosmetically. Declare them once on :root and reference them everywhere:\n\n:root {\n  --ink: #101010;\n  --accent: #1f3bff;\n}\n\nThe part I underused for too long is that they cascade and can be overridden by a selector. That is what makes theming almost free. A dark theme is not a second stylesheet, it is one block that redefines the same names:\n\n[data-theme=dark] {\n  --ink: #f2efe6;\n}\n\nEvery rule that already said var(--ink) now does the right thing. No duplication, no second file to keep in sync.\n\nTwo lessons from getting this wrong first. Name tokens by role, not by appearance: --accent survives a rebrand, --blue does not. And keep sizes and spacing in the shared block rather than the theme block, because layout should not change when colours do.',
 'Web Development', NOW() - INTERVAL 5 DAY),

((SELECT id FROM users WHERE username = 'pasindu'),
 'A CI pipeline you can actually read',
 'Our first pipeline was 300 lines of YAML that nobody understood. When it broke, the fix was to comment things out until it went green. That is not a pipeline, that is a slot machine.\n\nWe rewrote it around one rule: every step must be a command you can run on your own machine. If a step only works inside CI, you cannot debug it, and you will eventually be debugging it at 2am before a deadline.\n\nThat pushed all the real logic into a Makefile. The CI config became a thin wrapper: check out, install, run make lint, run make test. Twelve lines. When something fails, you run the same make target locally and get the same output.\n\nThe second rule was to fail fast and in order of cost. Linting takes two seconds, unit tests take thirty, integration tests take four minutes. Running them in that order means a missing semicolon does not cost you five minutes of waiting.\n\nCaching came last, deliberately. Caching a broken pipeline just makes it fail faster.',
 'DevOps', NOW() - INTERVAL 7 DAY),

((SELECT id FROM users WHERE username = 'alex_chen'),
 'Docker layer caching: one line, 6 minutes saved',
 'Our image took seven minutes to build. Almost all of it was reinstalling dependencies that had not changed, on every single commit.\n\nThe Dockerfile looked reasonable:\n\nCOPY . /app\nRUN composer install\n\nThat is the bug. COPY . invalidates the cache whenever any file changes, including a README typo. Every layer after it, including the slow install, gets rebuilt.\n\nThe fix is to copy only what the install step actually reads, do the install, then copy the rest:\n\nCOPY composer.json composer.lock ./\nRUN composer install\nCOPY . /app\n\nNow the dependency layer is only invalidated when the lock file changes. Build time went from seven minutes to about forty seconds for a normal commit.\n\nThe general principle is worth internalising: order Dockerfile instructions from least to most frequently changed. Your dependency manifests are stable, your source code is not. Most slow builds I have looked at since are some version of this same mistake.',
 'DevOps', NOW() - INTERVAL 11 DAY),

((SELECT id FROM users WHERE username = 'priya_patel'),
 'Training a Sinhala text classifier on a laptop GPU',
 'Most tutorials assume an A100 and a clean English dataset. I had a 6GB laptop GPU and 4,000 hand-labelled Sinhala news headlines. Here is what actually mattered.\n\nTokenisation was the whole game. Sinhala is heavily inflected, so a word-level vocabulary exploded past 40,000 entries with a long tail seen once or twice. Switching to SentencePiece with an 8,000-token vocabulary cut the embedding layer dramatically and improved accuracy by about four points, because rare inflected forms now shared subword units with common ones.\n\nBatch size was the constraint, not model size. I could fit a reasonable transformer, but only with a batch of 8, which made training noisy. Gradient accumulation over four steps gave me an effective batch of 32 without more memory, and the loss curve stopped looking like static.\n\nThe unglamorous win was data cleaning. Roughly 300 headlines had mixed Sinhala and English tokens; normalising those was worth more than any architecture change I tried.\n\nFinal numbers: 87% accuracy across six categories, about 20 minutes per training run. Good enough to be useful, which was the goal.',
 'Machine Learning', NOW() - INTERVAL 14 DAY),

((SELECT id FROM users WHERE username = 'pasindu'),
 'Your validation accuracy is probably lying to you',
 'I once reported 94% accuracy on a model that was useless in production. The number was real. The way I got it was not.\n\nThe first mistake was scaling before splitting. I fit the normaliser on the full dataset, then split into train and validation. That leaks the validation set mean and variance into training. The model gets information about data it is supposed to be judged on. Always split first, then fit any transform on the training portion only.\n\nThe second mistake was random splitting on time-ordered data. My samples were scraped over eight months and the underlying distribution drifted. Random splitting let the model see July when predicting June. A chronological split dropped accuracy to 78%, which was the honest number.\n\nThe third was tuning against the validation set for two weeks. After forty experiments choosing hyperparameters by validation score, that score is no longer an unbiased estimate. You have fit yourself to it. Keep a test set you touch exactly once.\n\nMy honest accuracy was 76%. Less impressive, and it actually held up when the model met real traffic.',
 'Machine Learning', NOW() - INTERVAL 18 DAY),

((SELECT id FROM users WHERE username = 'alex_chen'),
 'Finding an N+1 query with one log line',
 'A page that listed 50 articles with their authors took 2.3 seconds. The query I was worried about ran in 4ms.\n\nI added one line to log every statement with its duration. The output made it obvious: 51 queries. One to fetch the articles, then one more per article to fetch its author. That is the N+1 problem, and it is nearly invisible in code because the second query is usually hidden behind something that looks like a property access.\n\nThe fix is a join, so the database does the work once:\n\nSELECT blogPost.*, users.username\nFROM blogPost\nJOIN users ON blogPost.user_id = users.id\nORDER BY blogPost.created_at DESC\n\n51 queries became 1, and the page went to 40ms.\n\nWhat I took from it: latency problems are usually query count, not query speed. A 4ms query is fast, and fifty-one of them is still slow. Log the count before you start optimising individual statements, or you will spend an afternoon tuning something that was never the problem.',
 'Databases', NOW() - INTERVAL 21 DAY),

((SELECT id FROM users WHERE username = 'priya_patel'),
 'Indexes are not magic: reading MySQL EXPLAIN',
 'I added an index. The query got no faster. It took me an embarrassingly long time to learn to just ask the database why.\n\nPut EXPLAIN in front of the query and read three columns first: type, key, and rows.\n\ntype tells you the access strategy. ALL means a full table scan and is usually the thing to fix. ref or range means an index is being used. const is as good as it gets.\n\nkey tells you which index was actually chosen, which is often not the one you just created.\n\nrows is the estimate of how many rows MySQL expects to examine. Compare it to how many you expect back. If you want 10 rows and it plans to examine 90,000, the index is not doing its job.\n\nMy specific bug: the index existed but the query wrapped the column in a function. Once you write WHERE DATE(created_at) = ?, the index on created_at is unusable, because the index stores the raw value and the comparison happens on a computed one. Rewriting it as a range over the day made it a range scan.\n\nThe habit worth building is checking EXPLAIN before adding an index, not after. It tells you what the planner is missing.',
 'Databases', NOW() - INTERVAL 25 DAY),

((SELECT id FROM users WHERE username = 'pasindu'),
 'Prepared statements are not optional',
 'Every SQL injection I have found in a student project came from the same instinct: building a query as a string because it is the obvious thing to do.\n\n$sql = "SELECT * FROM users WHERE username = ''" . $name . "''";\n\nIf $name is an apostrophe followed by OR 1=1, the query means something you did not write. Escaping the input helps until you miss one path, and you will, because there is always one place where the value arrives from somewhere you forgot about.\n\nPrepared statements remove the category of bug rather than patching instances of it. The query and the data travel separately, so the data can never be parsed as syntax:\n\n$stmt = $pdo->prepare(''SELECT * FROM users WHERE username = ?'');\n$stmt->execute([$name]);\n\nOne detail worth setting explicitly: PDO emulates prepared statements by default with MySQL, meaning it interpolates client-side. Turn it off so you get real server-side ones:\n\nPDO::ATTR_EMULATE_PREPARES => false\n\nAnd note what placeholders cannot do. You cannot parameterise a table or column name. If those are dynamic, validate them against a fixed allowlist. That is the one place you still need care.',
 'Security', NOW() - INTERVAL 28 DAY),

((SELECT id FROM users WHERE username = 'alex_chen'),
 'CSRF explained with a form you can actually attack',
 'CSRF stayed abstract for me until I attacked my own site.\n\nI made a plain HTML page on a different port with a form posting to my app''s delete endpoint, and an onload that submitted it. Then I logged into my app in one tab and opened that page in another. The article was gone.\n\nNothing was stolen. The browser did exactly what it is designed to do: it attached my session cookie to a request to my own domain. The server saw a valid session and a well-formed request, and complied. It had no way to tell that I did not intend it.\n\nThe fix is proving intent. Put a random token in the session, embed it in every form, and reject any POST whose token does not match:\n\nif (!hash_equals($_SESSION[''token''], $_POST[''_token''] ?? '''')) { ... }\n\nThe attacker''s page cannot read your session, so it cannot guess the token. Use hash_equals rather than ==, so response timing does not leak how much of the token was correct.\n\nTwo things I got wrong initially. GET requests that change state cannot be protected this way, so do not have any: make deletes POST. And logout needs a token too, otherwise a page can forcibly sign you out, which is annoying rather than dangerous but still a bug.',
 'Security', NOW() - INTERVAL 32 DAY),

((SELECT id FROM users WHERE username = 'priya_patel'),
 'What strace taught me about a build that hung',
 'A build hung. No output, no error, no CPU usage. Killing and retrying worked about half the time, which is the worst kind of bug.\n\nWith no logs to read, I attached to the stuck process:\n\nstrace -p 14823\n\nOne line, repeated: a read on a file descriptor that never returned. The process was blocked waiting for input that was never coming.\n\nls -l /proc/14823/fd told me which descriptor it was: a pipe. The build script piped one command into another, and the reader was waiting on a writer that had already exited without closing cleanly. Half the time the timing worked out, half the time it deadlocked.\n\nWhat I actually learned is a way of narrowing things down. A hung process is doing one of three things: waiting on I/O, waiting on a lock, or spinning. strace separates the first two from the third in seconds. If you see a repeated blocking syscall, it is waiting. If you see nothing at all and CPU is at 100%, it is spinning and you want a profiler instead.\n\nI now reach for strace before adding print statements. It tells you what the process is doing rather than what you guessed it might be doing.',
 'Systems', NOW() - INTERVAL 36 DAY),

((SELECT id FROM users WHERE username = 'pasindu'),
 'Offline-first sync on Android without a library',
 'Our app had to work on campus wifi that drops constantly. Users type a note, lose connection, and expect it to be there later.\n\nThe design that survived contact with reality was a local write-ahead log. Every change writes to SQLite first and returns immediately, so the UI never waits on the network. A separate worker drains a queue of pending changes when connectivity returns.\n\nThe hard part is not sending. It is deciding what happens when the same record changed in two places. We tried last-write-wins and lost real user edits, because two devices'' clocks disagreed by nine minutes. Switching to a per-record version counter, where the server rejects a write whose base version is stale and the client re-applies it against the newer state, made conflicts explicit rather than silent.\n\nThe other thing that mattered: give every pending change a client-generated UUID. Without one, a retry after a timeout creates a duplicate, because the first request may well have succeeded before the response was lost. With one, the server can recognise the retry and ignore it.\n\nNo library, about 400 lines, and it handles the campus wifi fine.',
 'Mobile', NOW() - INTERVAL 40 DAY);

-- ---------------------------------------------------------
-- Demo cover images on a few articles, so the card grid shows
-- a mix of image cards and the coloured topic fallback.
--
-- These point at picsum.photos and therefore need internet access
-- to display. To go back to text-only cards for every article:
--     UPDATE blogPost SET image_url = NULL;
-- ---------------------------------------------------------

UPDATE blogPost SET image_url = 'https://picsum.photos/seed/mc-entry/1200/675'
    WHERE title = 'Why your PHP app should have exactly one entry point';

UPDATE blogPost SET image_url = 'https://picsum.photos/seed/mc-docker/1200/675'
    WHERE title = 'Docker layer caching: one line, 6 minutes saved';

UPDATE blogPost SET image_url = 'https://picsum.photos/seed/mc-sinhala/1200/675'
    WHERE title = 'Training a Sinhala text classifier on a laptop GPU';

UPDATE blogPost SET image_url = 'https://picsum.photos/seed/mc-csrf/1200/675'
    WHERE title = 'CSRF explained with a form you can actually attack';

UPDATE blogPost SET image_url = 'https://picsum.photos/seed/mc-strace/1200/675'
    WHERE title = 'What strace taught me about a build that hung';
