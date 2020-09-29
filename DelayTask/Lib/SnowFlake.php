<?php
namespace DelayTask\Lib;

use DelayTask\Exception\RuntimeException;

/**
 * 雪花算法
 * Class SnowFlake
 * @package DelayTask\Lib
 */
class SnowFlake
{
    const EPOCH_OFFSET = 1483200000000;
    // const SIGN_BITS = 1;
    const TIMESTAMP_BITS = 41;
    const DATACENTER_BITS = 5;
    const MACHINE_ID_BITS = 5;
    const SEQUENCE_BITS = 12;

    /**
     * @var mixed
     */
    protected $datacenterId;

    /**
     * @var mixed
     */
    protected $machineId;

    /**
     * @var null|int
     */
    protected $lastTimestamp = null;

    /**
     * @var int
     */
    protected $sequence = 1;
    protected $signLeftShift = self::TIMESTAMP_BITS + self::DATACENTER_BITS + self::MACHINE_ID_BITS + self::SEQUENCE_BITS;
    protected $timestampLeftShift = self::DATACENTER_BITS + self::MACHINE_ID_BITS + self::SEQUENCE_BITS;
    protected $dataCenterLeftShift = self::MACHINE_ID_BITS + self::SEQUENCE_BITS;
    protected $machineLeftShift = self::SEQUENCE_BITS;
    protected $maxSequenceId = -1 ^ (-1 << self::SEQUENCE_BITS);
    protected $maxMachineId = -1 ^ (-1 << self::MACHINE_ID_BITS);
    protected $maxDataCenterId = -1 ^ (-1 << self::DATACENTER_BITS);

    /**
     * Constructor to set required paremeters
     *
     * @param mixed $datacenterId Unique ID for datacenter (if multiple locations are used)
     * @param mixed $machineId Unique ID for machine (if multiple machines are used)
     * @throws RuntimeException
     */
    public function __construct($datacenterId, $machineId)
    {
        if ($datacenterId > $this->maxDataCenterId) {
            throw new RuntimeException('DataCenter id should between 0 and ' . $this->maxDataCenterId);
        }
        if ($machineId > $this->maxMachineId) {
            throw new RuntimeException('machine id should between 0 and ' . $this->maxMachineId);
        }
        $this->datacenterId = $datacenterId;
        $this->machineId = $machineId;
    }

    /**
     * Generate an unique ID based on SnowFlake
     * @return string
     * @throws RuntimeException
     */
    public function generateID()
    {
        $sign = 0; // default 0
        $timestamp = $this->getUnixTimestamp();
        if ($timestamp < $this->lastTimestamp) {
            throw new RuntimeException('"Clock moved backwards!');
        }
        if ($timestamp == $this->lastTimestamp) { //与上次时间戳相等，需要生成序列号
            $sequence = ++$this->sequence;
            if ($sequence == $this->maxSequenceId) { //如果序列号超限，则需要重新获取时间
                $timestamp = $this->getUnixTimestamp();
                while ($timestamp <= $this->lastTimestamp) {
                    $timestamp = $this->getUnixTimestamp();
                }
                $this->sequence = 0;
                $sequence = ++$this->sequence;
            }
        } else {
            $this->sequence = 0;
            $sequence = ++$this->sequence;
        }
        $this->lastTimestamp = $timestamp;
        $time = (int)($timestamp - self::EPOCH_OFFSET);
        $id = ($sign << $this->signLeftShift) | ($time << $this->timestampLeftShift) | ($this->datacenterId << $this->dataCenterLeftShift) | ($this->machineId << $this->machineLeftShift) | $sequence;
        return (string)$id;
    }

    /**
     * Get UNIX timestamp in microseconds
     *
     * @return int  Timestamp in microseconds
     */
    private function getUnixTimestamp()
    {
        return floor(microtime(true) * 1000);
    }
}