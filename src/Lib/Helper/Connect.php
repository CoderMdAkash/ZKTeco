<?php

namespace Rats\Zkteco\Lib\Helper;

use Rats\Zkteco\Lib\Helper\Util;
use Rats\Zkteco\Lib\ZKTeco;

class Connect
{
    /**
     * @param ZKTeco $self
     * @return bool
     */
    static public function connect(ZKTeco $self)
    {
        $self->_section = __METHOD__;

        $command = Util::CMD_CONNECT;
        $command_string = '';
        $chksum = 0;
        $session_id = 0;
        $reply_id = -1 + Util::USHRT_MAX;

        $buf = Util::createHeader($command, $chksum, $session_id, $reply_id, $command_string);

        if (socket_sendto($self->_zkclient, $buf, strlen($buf), 0, $self->_ip, $self->_port) === false) {
            return false;
        }

        $bytes = @socket_recvfrom($self->_zkclient, $self->_data_recv, 1024, 0, $self->_ip, $self->_port);
        if ($bytes === false || $bytes <= 0 || strlen($self->_data_recv) < 8) {
            return false;
        }

        $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6', substr($self->_data_recv, 0, 8));
        if (!is_array($u)) {
            return false;
        }

        $session = hexdec($u['h6'] . $u['h5']);
        if (empty($session)) {
            return false;
        }

        $self->_session_id = $session;
        return Util::checkValid($self->_data_recv);
    }

    /**
     * @param ZKTeco $self
     * @return bool
     */
    static public function disconnect(ZKTeco $self)
    {
        $self->_section = __METHOD__;

        $command = Util::CMD_EXIT;
        $command_string = '';
        $chksum = 0;
        $session_id = $self->_session_id;

        $reply_id = 0;
        if (strlen($self->_data_recv) >= 8) {
            $u = unpack('H2h1/H2h2/H2h3/H2h4/H2h5/H2h6/H2h7/H2h8', substr($self->_data_recv, 0, 8));
            if (is_array($u)) {
                $reply_id = hexdec($u['h8'] . $u['h7']);
            }
        }

        $buf = Util::createHeader($command, $chksum, $session_id, $reply_id, $command_string);


        if (socket_sendto($self->_zkclient, $buf, strlen($buf), 0, $self->_ip, $self->_port) === false) {
            return false;
        }

        $bytes = @socket_recvfrom($self->_zkclient, $self->_data_recv, 1024, 0, $self->_ip, $self->_port);
        if ($bytes === false || $bytes <= 0) {
            return false;
        }

        $self->_session_id = 0;
        return Util::checkValid($self->_data_recv);
    }
}
