<?php
namespace tests\service\resources\sales\v1;


use framework\message\Message;
use service\components\Tools;
use service\message\common\SourceEnum;
use service\resources\merchant\v1\home2;
use tests\service\ApplicationTest;

class getHome2Test extends ApplicationTest
{
    public function getModel()
    {
        return new home2();
    }

    public function testModel()
    {
        $this->assertInstanceOf('service\resources\merchant\v1\home2', $this->model);
    }

    public function testRequest()
    {
        $this->assertInstanceOf('service\message\core\HomeRequest', home2::request());
    }

    public function testResponse()
    {
        $this->assertInstanceOf('service\message\core\HomeResponse', home2::response());
    }

    public function testGetHeader()
    {
        $this->assertInstanceOf('service\message\common\Header', $this->header);
    }

    public function testGetRequest()
    {
        $this->assertInstanceOf('framework\Request', $this->request);
    }

	public function testRun()
	{
		$redis = Tools::getRedis();
		$redis->delete('merchant_home_page_v2');

		$this->request->setRemote(false);
		$request = home2::request();
		$request->setCustomerId($this->_customer_id);
		$request->setAuthToken($this->_auth_token);
		$this->header->setRoute('merchant.home2');
		$this->header->setSource(SourceEnum::MERCHANT);
		$rawBody = Message::pack($this->header, $request);
		$this->request->setRawBody($rawBody);
		$response = $this->application->handleRequest($this->request);
		$this->assertNotEmpty($response);
		/** @var \service\message\common\ResponseHeader $header */
		list($header, $data) = $response;
		$this->assertEquals(0, $header->getCode());
		$this->assertInstanceOf('service\message\core\HomeResponse', $data);

		//$this->assertEquals(4601, $header->getCode());
		//$this->assertFalse($data);

	}

}