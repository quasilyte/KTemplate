<?php

namespace KTemplate;

class Renderer {
    /** @var RendererState */
    private $state;

    public function __construct() {
        $this->state = new RendererState();
    }

    /**
     * @param Template $t
     * @param DataProviderInterface $data_provider
     * @return string
     */
    public function render($t, $data_provider) {
        $this->state->reset($data_provider);
        $this->doRender($t);
        return $this->state->buf;
    }

    /**
     * @param Template $t
     */
    private function doRender($t) {
        $state = $this->state;
        $key = $this->state->data_key;
        $pc = 0;
        $code = $t->code;

        /** @var mixed $slot0 */
        $slot0 = null;

        while (true) {
            $opdata = $code[$pc];
            $op = $opdata & 0xff;
            $pc++;

            switch ($op) {
            case Op::RETURN:
                return;

            case Op::OUTPUT_SLOT0:
                $state->buf .= $slot0;
                break;
            case Op::OUTPUT_STRING_CONST:
                $state->buf .= $t->string_values[($opdata >> 8) & 0xff];
                break;
            case Op::OUTPUT_INT_CONST:
                $state->buf .= $t->int_values[($opdata >> 8) & 0xff];
                break;
            case Op::OUTPUT_VAR_1:
                $key->num_parts = 1;
                $key->part1 = $t->string_values[($opdata >> 8) & 0xff];
                $state->buf .= $state->data_provider->getData($key);
                break;
            case Op::OUTPUT_VAR_2:
                $key->num_parts = 2;
                $key->part1 = $t->string_values[($opdata >> 8) & 0xff];
                $key->part2 = $t->string_values[($opdata >> 16) & 0xff];
                $state->buf .= $state->data_provider->getData($key);
                break;
            case Op::OUTPUT_VAR_3:
                $key->num_parts = 3;
                $key->part1 = $t->string_values[($opdata >> 8) & 0xff];
                $key->part2 = $t->string_values[($opdata >> 16) & 0xff];
                $key->part3 = $t->string_values[($opdata >> 24) & 0xff];
                $state->buf .= $state->data_provider->getData($key);
                break;
            
            case Op::LOAD_SLOT0_INT_CONST:
                $slot0 = $t->int_values[($opdata >> 8) & 0xff];
                break;
            
            case Op::JUMP:
                $pc += ($opdata >> 8) & 0xff;
                break;
            case Op::JUMP_ZERO:
                if ((int)$slot0 === 0) {
                    $pc += ($opdata >> 8) & 0xff;
                }
                break;
            case Op::JUMP_NOT_ZERO:
                if ((int)$slot0 !== 0) {
                    $pc += ($opdata >> 8) & 0xff;
                }
                break;

            default:
                return;
            }
        }
    }
}