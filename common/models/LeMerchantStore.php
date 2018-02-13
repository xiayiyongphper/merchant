<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "le_merchant_store".
 *
 * @property integer $entity_id
 * @property integer $merchant_id
 * @property string $user_name
 * @property string $password
 * @property string $store_name
 * @property string $customer_service_phone
 * @property string $shop_images
 * @property string $icon
 * @property string $logo
 * @property string $operate_time
 * @property string $deliver_region
 * @property string $min_trade_amount
 * @property integer $promised_delivery_time
 * @property string $contact_phone
 * @property integer $province
 * @property string $business_license_code
 * @property integer $city
 * @property integer $district
 * @property string $area_id
 * @property string $store_address
 * @property string $store_description
 * @property string $legal_representative_name
 * @property string $business_license_img
 * @property string $tax_registration_certificate_img
 * @property string $organization_code_certificate_img
 * @property string $business_category
 * @property string $lng
 * @property string $lat
 * @property string $created_at
 * @property string $updated_at
 * @property integer $is_info_complete
 * @property integer $status 
 * @property integer $sort
 * @property float $rebates
 * @property string $marketing_tags
 * @property string $category_tags
 * @property string $store_category
 * @property string $short_name
 *
 * @property CourierStoreCashierReports[] $courierStoreCashierReports
 * @property CourierStoreTransportCapacity[] $courierStoreTransportCapacities
 * @property LeImportLog[] $leImportLogs
 * @property LeMerchantBankinfo[] $leMerchantBankinfos
 * @property LeMerchantDelivery[] $leMerchantDeliveries
 * @property LeMerchantSettlement[] $leMerchantSettlements
 * @property LeMerchantSubsidies[] $leMerchantSubsidies
 * @property LeProductAssociation[] $leProductAssociations
 * @property LeProductTopSellers[] $leProductTopSellers
 * @property LeSearchProductQuery[] $leSearchProductQueries
 * @property LeStoreBannerActivityLog[] $leStoreBannerActivityLogs
 * @property LeTopicEventProduct[] $leTopicEventProducts
 * @property LeTopicEventStore[] $leTopicEventStores
 * @property LeTopicStore[] $leTopicStores
 * @property MerchantStoreFeaturedProduct[] $merchantStoreFeaturedProducts
 * @property MerchantStorePoster[] $merchantStorePosters
 * @property MerchantStorePosterProduct[] $merchantStorePosterProducts
 * @property MerchantStoreSummary[] $merchantStoreSummaries
 * @property MerchantUpdateProductLog[] $merchantUpdateProductLogs
 * @property ReportStoreCourierPerformance[] $reportStoreCourierPerformances
 * @property ReportStoreOrderObjective[] $reportStoreOrderObjectives
 * @property ReportStoreOrderStandard[] $reportStoreOrderStandards
 * @property ReportStoreParttimePerformance[] $reportStoreParttimePerformances
 * @property SalesFlatQuoteItem[] $salesFlatQuoteItems
 */
class LeMerchantStore extends \framework\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'le_merchant_store';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['merchant_id', 'shop_images', 'province', 'city', 'district', 'store_address', 'business_category'], 'required'],
            [['merchant_id', 'promised_delivery_time', 'province', 'city', 'district', 'is_info_complete', 'status','sort_score'], 'integer'],
            [['deliver_region', 'store_description', 'business_category'], 'string'],
            [['min_trade_amount'], 'number'],
            [['created_at', 'updated_at','store_category'], 'safe'],
            [['user_name', 'lng', 'lat'], 'string', 'max' => 32],
            [['password'], 'string', 'max' => 64],
            [['store_name', 'shop_images', 'operate_time', 'contact_phone', 'area_id', 'store_address', 'business_license_img', 'tax_registration_certificate_img', 'organization_code_certificate_img'], 'string', 'max' => 255],
            [['customer_service_phone'], 'string', 'max' => 200],
            [['icon', 'logo', 'legal_representative_name'], 'string', 'max' => 128],
            [['business_license_code'], 'string', 'max' => 50],
            [['user_name'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'entity_id' => 'Merchant Store ID',
            'merchant_id' => 'Merchant ID',
            'user_name' => 'user name',
            'password' => '商家APP登陆密码',
            'store_name' => 'Stire name',
            'customer_service_phone' => '客服电话',
            'shop_images' => 'Shop Images',
            'icon' => 'store icon',
            'logo' => 'store logo',
            'operate_time' => '营业时间',
            'deliver_region' => '配送区域',
            'min_trade_amount' => '最小交易金额',
            'promised_delivery_time' => '承诺送达时间，单位小时，默认72小时。',
            'contact_phone' => '联系电话',
            'province' => '省份',
            'business_license_code' => '营业执照注册号',
            'city' => '所在城市',
            'district' => '区域',
            'area_id' => '片区ID,多个片区ID，以|左右分割',
            'store_address' => '店铺地址',
            'store_description' => '店铺描述',
            'legal_representative_name' => 'Legal Representative Name',
            'business_license_img' => '营业执照照片',
            'tax_registration_certificate_img' => '税务登记证图片',
            'organization_code_certificate_img' => '组织机构代码证图片',
            'business_category' => '经营品类',
            'lng' => 'Lng',
            'lat' => 'Lat',
            'created_at' => 'Creation Time',
            'updated_at' => 'Update Time',
            'is_info_complete' => '店铺信息是否填写完整',
            'status' => '状态0：未审核，1:正常营业,2:暂停营业，3：封号，4：审核不通过',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCourierStoreCashierReports()
    {
        return $this->hasMany(CourierStoreCashierReports::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCourierStoreTransportCapacities()
    {
        return $this->hasMany(CourierStoreTransportCapacity::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLeImportLogs()
    {
        return $this->hasMany(LeImportLog::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLeMerchantBankinfos()
    {
        return $this->hasMany(LeMerchantBankinfo::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLeMerchantDeliveries()
    {
        return $this->hasMany(LeMerchantDelivery::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLeMerchantSettlements()
    {
        return $this->hasMany(LeMerchantSettlement::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLeMerchantSubsidies()
    {
        return $this->hasMany(LeMerchantSubsidies::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLeProductAssociations()
    {
        return $this->hasMany(LeProductAssociation::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLeProductTopSellers()
    {
        return $this->hasMany(LeProductTopSellers::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLeSearchProductQueries()
    {
        return $this->hasMany(LeSearchProductQuery::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLeStoreBannerActivityLogs()
    {
        return $this->hasMany(LeStoreBannerActivityLog::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLeTopicEventProducts()
    {
        return $this->hasMany(LeTopicEventProduct::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLeTopicEventStores()
    {
        return $this->hasMany(LeTopicEventStore::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLeTopicStores()
    {
        return $this->hasMany(LeTopicStore::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMerchantStoreFeaturedProducts()
    {
        return $this->hasMany(MerchantStoreFeaturedProduct::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMerchantStorePosters()
    {
        return $this->hasMany(MerchantStorePoster::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMerchantStorePosterProducts()
    {
        return $this->hasMany(MerchantStorePosterProduct::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMerchantStoreSummaries()
    {
        return $this->hasMany(MerchantStoreSummary::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMerchantUpdateProductLogs()
    {
        return $this->hasMany(MerchantUpdateProductLog::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReportStoreCourierPerformances()
    {
        return $this->hasMany(ReportStoreCourierPerformance::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReportStoreOrderObjectives()
    {
        return $this->hasMany(ReportStoreOrderObjective::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReportStoreOrderStandards()
    {
        return $this->hasMany(ReportStoreOrderStandard::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReportStoreParttimePerformances()
    {
        return $this->hasMany(ReportStoreParttimePerformance::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSalesFlatQuoteItems()
    {
        return $this->hasMany(SalesFlatQuoteItem::className(), ['store_id' => 'entity_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDelivery()
    {
        return $this->hasMany(LeMerchantDelivery::className(), ['store_id' => 'entity_id']);
    }
}
