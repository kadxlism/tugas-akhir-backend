<?php
  $manifest = json_decode(file_get_contents(public_path('build/.vite/manifest.json')), true);
  $main = $manifest['src/main.tsx'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>React in Laravel</title>

  
  <?php if(isset($main['css'])): ?>
    <?php $__currentLoopData = $main['css']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cssFile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <link rel="stylesheet" href="<?php echo e(asset('build/' . $cssFile)); ?>">
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
  <?php endif; ?>

  
  <script type="module" src="<?php echo e(asset('build/' . $main['file'])); ?>" defer></script>
</head>
<body>
  <div id="root"></div>
</body>
</html>
<?php /**PATH C:\Users\Hype G12\Documents\Herd\tugas-akhir-1710--main\backend\resources\views/app.blade.php ENDPATH**/ ?>