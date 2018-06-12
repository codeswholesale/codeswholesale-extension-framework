<?php
namespace CodesWholesaleFramework\Import;

use CodesWholesaleFramework\Model\ExternalProduct;
/**
 * Class CsvGenerator
 */
class CsvImportGenerator
{
    
    /**
     * @var CsvGenerator
     */
    protected $csvGenerator;
    
    /**
     * @var array
     */
    protected $changeLines = [];
    
    public function __construct() {
        $this->start();
    }
    
    public function start() {
        $this->csvGenerator = new CsvGenerator();
        $this->csvGenerator->setHeader($this->generateImportCsvHeader());
    }
    
    public function finish() {
       return $this->csvGenerator->generate();
    }
    
    
    public function appendNewProduct(ExternalProduct $externalProduct) {
        $this->csvGenerator->append($this->generateInsertLine($externalProduct));
    }
    
    public function appendUpdatedProduct(ExternalProduct $externalProduct, array $diff) {
        $this->csvGenerator->append($this->generateUpdateLine($externalProduct, $diff));
    }
    
    /**
     * @return array
     */
    private function generateImportCsvHeader(): array
    {
        return [
            'ID',
            'Status',
            'Name',
            'Price',
            'Stock',
            'Platform',
            'Regions',
            'Languages',
            'Cover',
        ];
    }
    
    /**
     * @param ExternalProduct $externalProduct
     *
     * @return array
     */
    private function generateInsertLine(ExternalProduct $externalProduct): array
    {
        return [
            (string) '"' . $externalProduct->getProduct()->getProductId() .'"',
            (string) '"' . 'Imported' .'"',
            (string) '"' . $externalProduct->getProduct()->getName() .'"',
            (string) '"' . $externalProduct->getProduct()->getLowestPrice() .'"',
            (string) '"' . $externalProduct->getProduct()->getStockQuantity() .'"',
            (string) '"' . ProductDiffGenerator::implodeArray($externalProduct->getProduct()->getPlatform()) .'"',
            (string) '"' . ProductDiffGenerator::implodeArray($externalProduct->getProduct()->getRegions()) .'"',
            (string) '"' . ProductDiffGenerator::implodeArray($externalProduct->getProduct()->getLanguages()) .'"',
            (string) '"' . $externalProduct->getProduct()->getImageUrl('MEDIUM') .'"',
        ];
    }
    
        /**
     * @param ExternalProduct $externalProduct
     *
     * @return array
     */
    private function generateUpdateLine(ExternalProduct $externalProduct, array $diffs): array
    {
        $this->changeLines = [];
        
        foreach ($diffs as $key => $diff) {
            $this->changeLines[$key] = 'Old: ' . $diff[ProductDiffGenerator::OLD_VALUE] . "\nNew: " .$diff[ProductDiffGenerator::NEW_VALUE] ;
        }

        $name = $this->getDiffLineByKey(
            ProductDiffGenerator::FIELD_NAME,
            $externalProduct->getProduct()->getName()
        );

        $platform = $this->getDiffLineByKey(
            ProductDiffGenerator::FIELD_PLATFORMS,
            ProductDiffGenerator::implodeArray($externalProduct->getProduct()->getPlatform())
        );

        $regions = $this->getDiffLineByKey(
            ProductDiffGenerator::FIELD_REGIONS,
            ProductDiffGenerator::implodeArray($externalProduct->getProduct()->getRegions())
        );

        $languages = $this->getDiffLineByKey(
            ProductDiffGenerator::FIELD_LANGUAGES,
            ProductDiffGenerator::implodeArray($externalProduct->getProduct()->getLanguages())
        );

        $price = $this->getDiffLineByKey(
            ProductDiffGenerator::FIELD_PRICE,
            $externalProduct->getProduct()->getLowestPrice()
        );

        $stock = $this->getDiffLineByKey(
            ProductDiffGenerator::FIELD_STOCK,
            $externalProduct->getProduct()->getStockQuantity()
        );

        $description = $this->getDiffLineByKey(
            ProductDiffGenerator::FIELD_DESCRIPTION,
            $externalProduct->getDescription()
        );

        $cover = $this->getDiffLineByKey(
            ProductDiffGenerator::FIELD_COVER,
            $externalProduct->getProduct()->getImageUrl('MEDIUM')
        );

        return [
            (string) '"' . $externalProduct->getProduct()->getProductId() . '"',
            (string) '"' . 'Updated' . '"',
            (string) '"' . $name . '"',
            (string) '"' . $price . '"',
            (string) '"' . $stock . '"',
            (string) '"' . $platform . '"',
            (string) '"' . $regions . '"',
            (string) '"' . $languages . '"',
           // (string) '"' . $description . '"',
            (string) '"' . $cover . '"',
        ];
    }
    
    private function getDiffLineByKey(string $key, $default): string
    {
        if (array_key_exists($key, $this->changeLines)) {
            return $this->changeLines[$key];
        } else {
            return $default;
        }
    }
}

