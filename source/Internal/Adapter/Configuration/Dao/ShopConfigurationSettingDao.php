<?php declare(strict_types=1);
/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\EshopCommunity\Internal\Adapter\Configuration\Dao;

use OxidEsales\EshopCommunity\Internal\Adapter\Configuration\DataObject\ShopConfigurationSetting;
use OxidEsales\EshopCommunity\Internal\Adapter\Configuration\Utility\ShopSettingEncoderInterface;
use OxidEsales\EshopCommunity\Internal\Adapter\ShopAdapterInterface;
use OxidEsales\EshopCommunity\Internal\Common\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Common\Exception\EntryDoesNotExistDaoException;
use OxidEsales\EshopCommunity\Internal\Utility\ContextInterface;

/**
 * @internal
 */
class ShopConfigurationSettingDao implements ShopConfigurationSettingDaoInterface
{
    /**
     * @var QueryBuilderFactoryInterface
     */
    private $queryBuilderFactory;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * @var ShopSettingEncoderInterface
     */
    private $shopSettingEncoder;

    /**
     * @var ShopAdapterInterface
     */
    private $shopAdapter;

    /**
     * ShopModuleSettingDao constructor.
     * @param QueryBuilderFactoryInterface $queryBuilderFactory
     * @param ContextInterface             $context
     * @param ShopSettingEncoderInterface  $shopSettingEncoder
     * @param ShopAdapterInterface         $shopAdapter
     */
    public function __construct(
        QueryBuilderFactoryInterface    $queryBuilderFactory,
        ContextInterface                $context,
        ShopSettingEncoderInterface     $shopSettingEncoder,
        ShopAdapterInterface            $shopAdapter
    ) {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->context = $context;
        $this->shopSettingEncoder = $shopSettingEncoder;
        $this->shopAdapter = $shopAdapter;
    }

    /**
     * @param ShopConfigurationSetting $shopConfigurationSetting
     */
    public function save(ShopConfigurationSetting $shopConfigurationSetting)
    {
        $this->delete($shopConfigurationSetting);

        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->insert('oxconfig')
            ->values([
                'oxid'          => ':id',
                'oxshopid'      => ':shopId',
                'oxvarname'     => ':name',
                'oxvartype'     => ':type',
                'oxvarvalue'    => 'encode(:value, :key)',
            ])
            ->setParameters([
                'id'        => $this->shopAdapter->generateUniqueId(),
                'shopId'    => $shopConfigurationSetting->getShopId(),
                'name'      => $shopConfigurationSetting->getName(),
                'type'      => $shopConfigurationSetting->getType(),
                'value'     => $this->shopSettingEncoder->encode(
                    $shopConfigurationSetting->getType(),
                    $shopConfigurationSetting->getValue()
                ),
                'key'       => $this->context->getConfigurationEncryptionKey(),
            ]);

        $queryBuilder->execute();
    }

    /**
     * @param string $name
     * @param int    $shopId
     * @return ShopConfigurationSetting
     * @throws EntryDoesNotExistDaoException
     */
    public function get(string $name, int $shopId): ShopConfigurationSetting
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->select('decode(oxvarvalue, :key) as value, oxvartype as type, oxvarname as name')
            ->from('oxconfig')
            ->where('oxshopid = :shopId')
            ->andWhere('oxvarname = :name')
            ->andWhere('oxmodule = ""')
            ->setParameters([
                'shopId'    => $shopId,
                'name'      => $name,
                'key'       => $this->context->getConfigurationEncryptionKey(),
            ]);

        $result = $queryBuilder->execute()->fetch();

        if (false === $result) {
            throw new EntryDoesNotExistDaoException();
        }

        $setting = new ShopConfigurationSetting();
        $setting
            ->setName($name)
            ->setValue($this->shopSettingEncoder->decode($result['type'], $result['value']))
            ->setShopId($shopId)
            ->setType($result['type']);

        return $setting;
    }

    /**
     * @param ShopConfigurationSetting $setting
     */
    public function delete(ShopConfigurationSetting $setting)
    {
        $queryBuilder = $this->queryBuilderFactory->create();
        $queryBuilder
            ->delete('oxconfig')
            ->where('oxshopid = :shopId')
            ->andWhere('oxvarname = :name')
            ->andWhere('oxmodule = ""')
            ->setParameters([
                'shopId'    => $setting->getShopId(),
                'name'      => $setting->getName(),
            ]);

        $queryBuilder->execute();
    }
}
