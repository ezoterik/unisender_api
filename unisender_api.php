<?php

/**
 * API UniSender
 *
 * @see http://www.unisender.com/ru/help/api/
 * @version 1.3
 *
 * @method string|bool getLists(array $params) получить списки рассылки с их кодами
 * @method string|bool createList(array $params) создать новый список рассылки
 * @method string|bool updateList(array $params) изменить свойства списка рассылки
 * @method string|bool deleteList(array $params) удалить список рассылки
 * @method string|bool exclude(array $params) исключить адресата из списков рассылки
 * @method string|bool unsubscribe(array $params) отписать адресата от рассылки
 * @method string|bool importContacts(array $params) метод массового импорта подписчиков
 * @method string|bool exportContacts(array $params) экспорт всех данных контактов
 *
 * @method string|bool createEmailMessage(array $params) создать e-mail для массовой рассылки
 * @method string|bool createSmsMessage(array $params) создать SMS для массовой рассылки
 * @method string|bool createCampaign(array $params) запланировать массовую отправку e-mail или SMS сообщения
 * @method string|bool getCampaigns(array $params) получить список рассылок
 * @method string|bool getCampaignStatus(array $params) получить статус рассылки
 * @method string|bool sendSms(array $params) отправить SMS-сообщение
 * @method string|bool checkSms(array $params) проверить статус доставки SMS
 * @method string|bool sendEmail(array $params) упрощённая отправка индивидуальных email-сообщений
 * @method string|bool checkEmail(array $params) проверить статус доставки email
 * @method string|bool updateOptInEmail(array $params) изменить текст письма со ссылкой подтверждения подписки
 * @method string|bool deleteMessage(array $params) удалить сообщение
 *
 * @method string|bool getCampaignDeliveryStats(array $params) получить отчёт о статусах доставки сообщений для заданной рассылки
 * @method string|bool getCampaignAggregateStats(array $params) получить отчёт о статусах доставки сообщений для заданной рассылки, сгруппированных по типам результата
 * @method string|bool getVisitedLinks(array $params) получить статистику переходов по ссылкам
 *
 * @method string|bool getFields(array $params) получить список пользовательских полей
 * @method string|bool createField(array $params) создать новое поле
 * @method string|bool updateField(array $params) изменить параметры поля
 * @method string|bool deleteField(array $params) удалить поле
 * @method string|bool getTags(array $params) получить список пользовательских меток
 * @method string|bool deleteTag(array $params) удалить метку
 *
 * @method string|bool validateSender(array $params) подтвердить обратный адрес
 * @method string|bool register(array $params) зарегистрировать пользователя (для партнёрских сайтов)
 * @method string|bool checkUserExists(array $params) проверить существование пользователя по логину или email
 * @method string|bool getUserInfo(array $params) получить информацию о пользователе (для партнёрских сайтов)
 * @method string|bool getUsers(array $params) получить информацию о пользователях (для партнёрских сайтов)
 * @method string|bool transferMoney(array $params) перевести деньги на счёт пользователя (для партнёрских сайтов)
 * @method string|bool getPayments(array $params) получить список платежей пользователя (для партнёрских сайтов)
 * @method string|bool getTariffs(array $params) получить список тарифов (для партнёрских сайтов)
 * @method string|bool changeTariff(array $params) переключить тариф пользователю (для партнёрских сайтов)
 */
class UniSenderApi {
	/**
	 * @var string
	 */
	protected $ApiKey;

	/**
	 * @var string
	 */
	protected $Encoding = 'UTF8';

	/**
	 * @var int
	 */
	protected $RetryCount = 0;

	/**
	 * @var float
	 */
	protected $Timeout;

	/**
	 * @var bool
	 */
	protected $Compression = false;

	/**
	 * @param string $ApiKey
	 * @param string $Encoding
	 * @param int $RetryCount
	 */
	function __construct($ApiKey, $Encoding = 'UTF8', $RetryCount = 4, $Timeout = null, $Compression = false) {
		$this->ApiKey = $ApiKey;

		if (!empty($Encoding)) {
			$this->Encoding = $Encoding;
		}

		if (!empty($RetryCount)) {
			$this->RetryCount = $RetryCount;
		}

		if (!empty($Timeout)) {
			$this->Timeout = $Timeout;
		}

		if ($Compression) {
			$this->Compression = $Compression;
		}
	}

	/**
	 * @param string $Name
	 * @param array $Arguments
	 * @return string
	 */
	function __call($Name, $Arguments) {
		if (!is_array($Arguments) || empty($Arguments)) {
			$Params = array();
		} else {
			$Params = $Arguments[0];
		}

		return $this->callMethod($Name, $Params);
	}

	/**
	 * @param array $Params
	 * @return string
	 */
	function subscribe($Params) {
		$Params = (array)$Params;

		if (empty($Params['request_ip'])) {
			$Params['request_ip'] = $this->getClientIp();
		}

		return $this->callMethod('subscribe', $Params);
	}

	/**
	 * @param string $JSON
	 * @return mixed
	 */
	protected function decodeJSON($JSON) {
		return json_decode($JSON);
	}

	/**
	 * @return string
	 */
	protected function getClientIp() {
		$Result = '';

		if (!empty($_SERVER["REMOTE_ADDR"])) {
			$Result = $_SERVER["REMOTE_ADDR"];
		} else if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"]))  {
			$Result = $_SERVER["HTTP_X_FORWARDED_FOR"];
		} else if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
			$Result = $_SERVER["HTTP_CLIENT_IP"];
		}

		if (preg_match('/([0-9]|[0-9][0-9]|[01][0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[0-9][0-9]|[01][0-9][0-9]|2[0-4][0-9]|25[0-5])){3}/', $Result, $Match)) {
			return $Match[0];
		}

		return $Result;
	}

	/**
	 * @param string $Value
	 * @param string $Key
	 */
	protected function iconv(&$Value, $Key) {
		$Value = iconv($this->Encoding, 'UTF8//IGNORE', $Value);
	}

	/**
	 * @param string $Value
	 * @param string $Key
	 */
	protected function mb_convert_encoding(&$Value, $Key) {
		$Value = mb_convert_encoding($Value, 'UTF8', $this->Encoding);
	}

	/**
	 * @param string $MethodName
	 * @param array $Params
	 * @return array
	 */
	protected function callMethod($MethodName, $Params = array()) {
		if ($this->Encoding != 'UTF8') {
			if (function_exists('iconv')) {
				array_walk_recursive($Params, array($this, 'iconv'));
			} else if (function_exists('mb_convert_encoding')) {
				array_walk_recursive($Params, array($this, 'mb_convert_encoding'));
			}
		}

		$Url = $MethodName . '?format=json';

		if ($this->Compression) {
			$Url .= '&api_key=' . $this->ApiKey . '&request_compression=bzip2';
			$Content = bzcompress(http_build_query($Params));
		} else {
			$Params = array_merge((array)$Params, array('api_key' => $this->ApiKey));
			$Content = http_build_query($Params);
		}

		$ContextOptions = array(
			'http' => array(
				'method'  => 'POST',
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => $Content,
			)
		);

		if ($this->Timeout) {
			$ContextOptions['http']['timeout'] = $this->Timeout;
		}

		$RetryCount = 0;
		$Context = stream_context_create($ContextOptions);

		do {
			$Host = $this->getApiHost($RetryCount);
			$Result = @file_get_contents($Host . $Url, false, $Context);
			$RetryCount++;
		} while ($Result === false && $RetryCount < $this->RetryCount);

		return $Result;
	}

	/**
	 * @param int $RetryCount
	 * @return string
	 */
	protected function getApiHost($RetryCount = 0) {
		if ($RetryCount % 2 == 0) {
			return 'http://api.unisender.com/ru/api/';
		} else {
			return 'http://www.api.unisender.com/ru/api/';
		}
	}
}