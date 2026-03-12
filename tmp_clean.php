<?php
$file = 'src/Entity/MediaObject.php';
$content = file_get_contents($file);

// Remove property
$content = preg_replace('/#\[ORM\\\\Column\(length: 255, enumType: App\\\\Enum\\\\MediaCategory::class\)\]\s+#\[Assert\\\\NotNull\]\s+private \?App\\\\Enum\\\\MediaCategory \$category = null;/m', '', $content);
$content = preg_replace('/#\[ORM\\\\Column\(length: 255, enumType: MediaCategory::class\)\]\s+#\[Assert\\\\NotNull\]\s+private \?MediaCategory \$category = null;/m', '', $content);

// Remove getter/setter
$content = preg_replace('/public function getCategory\(\): \?MediaCategory\s+{.*?public function setCategory\(MediaCategory \$category\): static\s+{.*?return \$this;\s+}/ms', '', $content);

// Remove use App\Enum\MediaCategory;
$content = preg_replace('/use App\\\\Enum\\\\MediaCategory;\s*/', '', $content);

file_put_contents($file, $content);
