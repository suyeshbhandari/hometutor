<?php
require_once 'includes/functions.php';

// 3 top-rated approved tutors for the home page
$top = db_all("SELECT u.id, u.name, tp.subjects, tp.location, tp.price_per_hour, tp.experience_years,
                      COALESCE(AVG(r.rating), 0) AS avg_rating, COUNT(r.id) AS review_count
               FROM users u
               JOIN tutor_profiles tp ON tp.user_id = u.id
               LEFT JOIN reviews r ON r.tutor_id = u.id
               WHERE u.role = 'tutor' AND u.status = 'approved' AND tp.subjects <> ''
               GROUP BY u.id, u.name, tp.subjects, tp.location, tp.price_per_hour, tp.experience_years
               ORDER BY avg_rating DESC, review_count DESC, u.name ASC
               LIMIT 3");

$page_title = 'Home';
include 'includes/header.php';
?>
<section class="hero">
  <h1>Find the right home tutor, easily</h1>
  <p>Search qualified tutors by subject and location, send a booking request, and manage everything online.</p>
  <form method="get" action="tutors.php" class="hero-search">
    <input type="text" name="subject" placeholder="Subject (e.g. Mathematics)" maxlength="50">
    <input type="text" name="location" placeholder="Location (e.g. Lalitpur)" maxlength="50">
    <button type="submit" class="btn btn-primary">Search</button>
  </form>
</section>

<section>
  <h2>How it works</h2>
  <div class="grid grid-3">
    <div class="card center"><div class="big">1</div><h3>Register</h3><p>Create a free account as a student/parent or as a tutor.</p></div>
    <div class="card center"><div class="big">2</div><h3>Search &amp; Book</h3><p>Filter tutors by subject, location and rating, then send a request.</p></div>
    <div class="card center"><div class="big">3</div><h3>Learn</h3><p>The tutor accepts, you study together, and you leave a review.</p></div>
  </div>
</section>

<section>
  <h2>Top rated tutors</h2>
  <?php if (!$top): ?>
    <p class="muted">No tutors are listed yet.</p>
  <?php else: ?>
    <div class="grid grid-3">
      <?php foreach ($top as $t): ?>
        <div class="card">
          <h3><?= e($t['name']) ?></h3>
          <p class="rating"><?= $t['review_count'] > 0 ? stars($t['avg_rating']) . ' ' . number_format($t['avg_rating'], 1) . ' (' . (int)$t['review_count'] . ')' : 'No reviews yet' ?></p>
          <p><strong>Subjects:</strong> <?= e($t['subjects']) ?></p>
          <p><strong>Location:</strong> <?= e($t['location']) ?></p>
          <p><strong>Fee:</strong> Rs. <?= (int)$t['price_per_hour'] ?> / hour</p>
          <a class="btn btn-primary btn-small" href="tutor_view.php?id=<?= (int)$t['id'] ?>">View profile</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php include 'includes/footer.php'; ?>
