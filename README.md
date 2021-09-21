 ### 校验规则，按优先级顺序排列
 1. 具有“set”属性的扩展规则（详见：buildValidators#扩展规则)
 2. 当前类自带的校验规则（ 不同的类对attribute设定的校验函数）
 3. 框架默认的校验规则（详见：buildValidators#框架默认规则)
 4. 自定义的校验规则 (详见：ValidatorCustomize::buildValidators)