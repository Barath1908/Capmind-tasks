</div><!-- /.page-wrap -->

<footer>
  HealthCore &mdash; Patient Visit &amp; Follow-Up Manager &copy;
  <?php
  // SQL for current year — no PHP date()
  $yr = $conn->query("SELECT YEAR(CURDATE()) AS y")->fetch_assoc()['y'];
  echo $yr;
  ?>
  &mdash; All date calculations performed entirely via SQL
</footer>

</body>
</html>
