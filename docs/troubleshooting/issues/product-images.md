# Product image Issues

## Errors

* Imported resource (image) could not be downloaded from external resource due to timeout or access permissions in sku(s)

## Identify problem

0. Find the images that will be imported for the affected product:

	<!-- -->

		mysql> select image_1,image_2,image_3,image_4,image_5,image_6,image_7,image_8,image_9,image_10 from m2m_product where sku = "<sku>";

### Option 1: Missing Image

0. Check whether any image is missing in the Graphics/Bild folder:

	<!-- -->

		find magento/pub/Graphics/Bild -name "<image_name_without_underscore>*" -type f


### Option 2: Incorrect Image Type

1. Create following php script:

	<!-- -->

		vim checkImageType.php

	<!-- -->

		<?php

		$allowedTypes = [
    		IMAGETYPE_GIF => 'GIF',
    		IMAGETYPE_JPEG => 'JPEG',
    		IMAGETYPE_PNG => 'PNG',
    		IMAGETYPE_XBM => 'XBM',
    		IMAGETYPE_WBMP => 'WBMP',
		];

		list($width, $height, $type, $attr) = getimagesize("<image_absolute_path>");

		if (isset($allowedTypes[$type])) {
    		echo sprintf('Image type (%s) is valid: %s', $type, $allowedTypes[$type]) . PHP_EOL;
		} else {
    		echo sprintf('Not valid image type (%s). Valid Image types are: %s', $type, implode(',', array_keys($allowedTypes))) . PHP_EOL;
    		echo "Check php documentation to get more info about the type of this image: http://php.net/manual/en/image.constants.php" . PHP_EOL;
		}

2. Run script

	<!-- -->

		chmod +x checkImageType.php
		php checkImageType.php



