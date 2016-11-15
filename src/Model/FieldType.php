<?php

namespace Tivoh\Model;

class FieldType {
    const Bool = \PDO::PARAM_BOOL;
    const Int = \PDO::PARAM_INT;
    const String = \PDO::PARAM_STR;
    const Null = \PDO::PARAM_NULL;
}
