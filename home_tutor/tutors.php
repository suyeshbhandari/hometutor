<?php
require_once 'includes/functions.php';

$subject  = get_text('subject');
$location = get_text('location');
$min      = get_text('min_rating');
$sort     = get_text('sort');

// only accept known values
if (!in_array($min, array('1', '2', '3', '4'), true)) $min = '';
$allowed_sort = array(
    'rating'     => 'avg_rating DESC, review_count DESC, u.name ASC',
    'price_low'  => 'tp.price_per_hour ASC, u.name ASC',
    'experience' => 'tp.experience_years DESC, u.name ASC'
);
if (!isset($allowed_sort[$sort])) $sort = 'rating';

$sql = "SELECT u.id, u.name, tp.subjects, tp.qualification, tp.location, tp.price_per_hour, tp.experience_years,
               COALESCE(AVG(r.rating), 0) AS avg_rating, COUNT(r.id) AS review_count
        FROM users u
        JOIN tutor_profiles tp ON tp.user_id = u.id
        LEFT JOIN reviews r ON r.tutor_id = u.id
        WHERE u.role = 'tutor' AND u.status = 'approved' AND tp.subjects <> ''";
$types = '';
$params = array();

if ($subject !== '') {
    $sql .= " AND tp.subjects LIKE ?";
    $types .= 's';
    $params[] = '%' . $subject . '%';
}
if ($location !== '') {
    $sql .= " AND tp.location LIKE ?";
    $types .= 's';
    $params[] = '%' . $location . '%';
}
$sql .= " GROUP BY u.id, u.name, tp.subjects, tp.qualification, tp.location, tp.price_per_hour, tp.experience_years";
if ($min !== '') {
    $sql .= " HAVING avg_rating >= ?";
    $types .= 'd';
    $params[] = (float)$min;
}
$sql .= " ORDER BY " . $allowed_sort[$sort];

$tutors = db_all($sql, $types, $params);

$page_title = 'Find Tutors';
include 'includes/header.php';
?>
<h1>Find a tutor</h1>

<form method="get" action="tutors.php" class="filter-bar">
  <input type="text" name="subject" placeholder="Subject" maxlength="50" value="<?= e($subject) ?>">
  <input type="text" name="location" placeholder="Location" maxlength="50" value="<?= e($location) ?>">
  <select name="min_rating">
    <option value="">Any rating</option>
    <?php foreach (array('4', '3', '2', '1') as $v): ?>
      <option value="<?= $v ?>" <?= $min === $v ? 'selected' : '' ?>><?= $v ?>+ stars</option>
    <?php endforeach; ?>
  </select>
  <select name="sort">
    <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Sort: Best rated</option>
    <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Sort: Lowest fee</option>
    <option value="experience" <?= $sort === 'experience' ? 'selected' : '' ?>>Sort: Most experienced</option>
  </select>
  <button type="submit" class="btn btn-primary">Search</button>
  <a href="tutors.php" class="btn btn-outline">Reset</a>
</form>

<p class="muted"><?= count($tutors) ?> tutor(s) found.</p>

<?php if (!$tutors): ?>
  <div class="card center"><p>No tutors match your search. Try fewer filters.</p></div>
<?php else: ?>
  <div class="grid grid-3">
    <?php foreach ($tutors as $t): ?>
      <div class="card">
        <h3><?= e($t['name']) ?></h3>
        <p class="rating"><?= $t['review_count'] > 0 ? stars($t['avg_rating']) . ' ' . number_format($t['avg_rating'], 1) . ' (' . (int)$t['review_count'] . ')' : 'No reviews yet' ?></p>
        <p><strong>Subjects:</strong> <?= e($t['subjects']) ?></p>
        <p><strong>Qualification:</strong> <?= e($t['qualification']) ?></p>
        <p><strong>Experience:</strong> <?= (int)$t['experience_years'] ?> year(s)</p>
        <p><strong>Location:</strong> <?= e($t['location']) ?></p>
        <p><strong>Fee:</strong> Rs. <?= (int)$t['price_per_hour'] ?> / hour</p>
        <a class="btn btn-primary btn-small" href="tutor_view.php?id=<?= (int)$t['id'] ?>">View &amp; book</a>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
