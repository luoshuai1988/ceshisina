<?php
namespace command;

/**
 * 创建文本命令
 */
class OrderCreate implements Order
{
  /**
   * 文本类实体
   * @var object
   */
    private $text;

  /**
   * 命令参数
   * @var array
   */
    private $arguments = [
    'filename' => ''
    ];

  /**
   * 构造函数
   *
   * @param Text $text
   * @param array $arguments
   */
    public function __construct(Text $text, $arguments = [])
    {
        $this->text      = $text;
        $this->arguments = $arguments;
    }

  /**
   * 执行命令
   *
   * @return void
   */
    public function execute()
    {
        $this->text->create($this->arguments['filename']);
    }
}
