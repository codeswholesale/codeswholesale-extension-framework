<?php
namespace CodesWholesaleFramework\Database\Factories;

use CodesWholesaleFramework\Model\ExternalProduct;
use CodesWholesaleFramework\Database\Interfaces\DbManagerInterface;
use CodesWholesaleFramework\Database\Models\CodeswholesaleProductModel;
use CodesWholesaleFramework\Database\Repositories\CodeswholesaleProductRepository;

/**
 * Class CodeswholesaleProductModelFactory
 */
class CodeswholesaleProductModelFactory
{
    /**
     * @var CodeswholesaleProductRepository
     */
    private $repository;

    public function __construct(DbManagerInterface $db) {
        $this->repository = new CodeswholesaleProductRepository($db);
    }

    public static function resolve(\stdClass $parameters): CodeswholesaleProductModel
    {
        $model = new CodeswholesaleProductModel();

        $id = CodeswholesaleProductRepository::FIELD_ID;
        $productId = CodeswholesaleProductRepository::FIELD_PRODUCT_ID;
        $createdAt = CodeswholesaleProductRepository::FIELD_CREATED_AT;
        $description = CodeswholesaleProductRepository::FIELD_DESCRIPTION;
        $title = CodeswholesaleProductRepository::FIELD_TITLE;
        $image = CodeswholesaleProductRepository::FIELD_IMAGE;
        $gallery = CodeswholesaleProductRepository::FIELD_GALLERY;
        $preferredLanguage  = CodeswholesaleProductRepository::FIELD_PREFERRED_LANGUAGE;

        $model
            ->setId($parameters->$id)
            ->setProductId($parameters->$productId)
            ->setCreatedAt(new \DateTime($parameters->$createdAt))
            ->setDescription($parameters->$description)
            ->setTitle($parameters->$title)
            ->setImage($parameters->$image)
            ->setGallery($parameters->$gallery)
            ->setPreferredLanguage($parameters->$preferredLanguage)
        ;

        return $model;
    }

    public function create(ExternalProduct $externalProduct, $lang)
    {
        if($this->repository->isset($externalProduct->getProduct()->getProductId(), $lang)) {
            $this->update($externalProduct, $this->prepare($externalProduct->getProduct()->getProductId(), $lang));

            return;
        }

        $cwModel =  new CodeswholesaleProductModel();

        $cwModel->setProductId($externalProduct->getProduct()->getProductId());
        $cwModel->setDescription($externalProduct->getDescription());
        $cwModel->setTitle($externalProduct->getProduct()->getName());
        $cwModel->setPreferredLanguage($lang);

        $thumb = $externalProduct->getThumbnail();

        if($thumb && $thumb['name']) {
            $cwModel->setImage($thumb['name']) ;
        }

        $photos = [];

        foreach($externalProduct->getPhotos() as $photo) {
            $photos[] = $photo['name'];
        }

        $cwModel->setGallery(implode("|", $photos));

        $this->repository->save($cwModel);
    }

    public function prepare(ExternalProduct $externalProduct, $lang)
    {
        try {
            return $this->repository->find($externalProduct->getProduct()->getProductId(), $lang);
        } catch (\Exception $ex) {
            $this->create($externalProduct, $lang);
            return $this->prepare($externalProduct, $lang);
        }
    }

    public function update(ExternalProduct $externalProduct, CodeswholesaleProductModel $cwModel)
    {
        $cwModel->setDescription($externalProduct->getDescription());
        $cwModel->setTitle($externalProduct->getProduct()->getName());
        $thumb = $externalProduct->getThumbnail();

        if($thumb && $thumb['name']) {
            $cwModel->setImage($thumb['name']) ;
        }

        $photos = [];

        foreach($externalProduct->getPhotos() as $photo) {
            $photos[] = $photo['name'];
        }

        $cwModel->setGallery(implode("|", $photos));

        $this->repository->overwrite($cwModel);
    }
}