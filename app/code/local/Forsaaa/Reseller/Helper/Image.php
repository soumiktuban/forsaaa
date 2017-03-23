<?php

class Forsaaa_Reseller_Helper_Image extends Mage_Core_Helper_Abstract
{
    public function getCategoryImage(Mage_Catalog_Model_Category $category, $width = null, $height = null)
    {
        // return when no image exists
        if (!$category->getImage()) {
            return false;
        }

        // return when the original image doesn't exist
        $imagePath = Mage::getBaseDir('media') . DS . 'catalog' . DS . 'category'
            . DS . $category->getImage();
        if (!file_exists($imagePath)) {
            return false;
        }

        // resize the image if needed
        $rszImagePath = Mage::getBaseDir('media') . DS . 'catalog' . DS . 'category'
            . DS . 'cache' . DS . $width . 'x' . $height . DS
            . $category->getImage();
        if (!file_exists($rszImagePath)) {
            $image = new Varien_Image($imagePath);
            $image->resize($width,$height);
            $image->save($rszImagePath);
        }

        // return the image URL
        return Mage::getBaseUrl('media') . '/catalog/category/cache/' . $width . 'x'
            . $height . '/' . $category->getImage();
    }
}