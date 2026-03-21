import { resolve, isArray, mergeKeys, isEmpty, isNotEmpty } from '@primeuix/utils';
import { reactive, computed, mergeProps, getCurrentInstance, onMounted, nextTick, ref, watch, toValue } from 'vue';

function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), true).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: true, configurable: true, writable: true }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = false, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = true, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), true), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
function _slicedToArray(r, e) { return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest(); }
function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _iterableToArrayLimit(r, l) { var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"]; if (null != t) { var e, n, i, u, a = [], f = true, o = false; try { if (i = (t = t.call(r)).next, 0 === l) ; else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0); } catch (r) { o = true, n = r; } finally { try { if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return; } finally { if (o) throw n; } } return a; } }
function _arrayWithHoles(r) { if (Array.isArray(r)) return r; }
function tryOnMounted(fn) {
  var sync = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
  if (getCurrentInstance()) onMounted(fn);else if (sync) fn();else nextTick(fn);
}
function watchPausable(source, callback, options) {
  var isActive = ref(true);
  var stop = watch(source, function (newValue, oldValue) {
    if (!isActive.value) return;
    callback(newValue, oldValue);
  }, options);
  return {
    stop: stop,
    pause: function pause() {
      isActive.value = false;
    },
    resume: function resume() {
      isActive.value = true;
    }
  };
}

// @todo: move to utils
function groupKeys(obj) {
  return Object.entries(obj).reduce(function (result, _ref) {
    var _ref2 = _slicedToArray(_ref, 2),
      key = _ref2[0],
      value = _ref2[1];
    /* eslint-disable-next-line no-useless-escape */
    key.split(/[\.\[\]]+/).filter(Boolean).reduce(function (acc, curr, idx, arr) {
      var _acc$curr;
      return (_acc$curr = acc[curr]) !== null && _acc$curr !== void 0 ? _acc$curr : acc[curr] = isNaN(arr[idx + 1]) ? idx === arr.length - 1 ? value : {} : [];
    }, result);
    return result;
  }, {});
}
function getValueByPath(obj, path) {
  if (!obj || !path) {
    // short circuit if there is nothing to resolve
    return null;
  }
  try {
    var value = obj[path];
    if (isNotEmpty(value)) return value;
  } catch (_unused) {
    // do nothing and continue to other methods to resolve path data
  }

  /* eslint-disable-next-line no-useless-escape */
  var keys = path.split(/[\.\[\]]+/).filter(Boolean);
  return keys.reduce(function (acc, key) {
    return acc && acc[key] !== undefined ? acc[key] : undefined;
  }, obj);
}
var useForm = function useForm() {
  var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
  var _states = reactive({});
  var fields = reactive({});
  var valid = computed(function () {
    return Object.values(_states).every(function (field) {
      return !field.invalid;
    });
  });
  var states = computed(function () {
    return groupKeys(_states);
  });
  var getInitialState = function getInitialState(field, initialValue) {
    return {
      value: initialValue !== null && initialValue !== void 0 ? initialValue : getValueByPath(options.initialValues, field),
      touched: false,
      dirty: false,
      pristine: true,
      valid: true,
      invalid: false,
      error: null,
      errors: []
    };
  };
  var isFieldValidate = function isFieldValidate(field, validateOn) {
    var value = resolve(validateOn, field);
    return value === true || isArray(value) && value.includes(field);
  };
  var validateOn = /*#__PURE__*/function () {
    var _ref3 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee(option, defaultValue) {
      var _options$option;
      var results, fieldArr, _t, _t2;
      return _regenerator().w(function (_context) {
        while (1) switch (_context.n) {
          case 0:
            results = {};
            if (!isArray(options[option])) {
              _context.n = 2;
              break;
            }
            _context.n = 1;
            return validate(options[option]);
          case 1:
            results = _context.v;
            _context.n = 4;
            break;
          case 2:
            _t = (_options$option = options[option]) !== null && _options$option !== void 0 ? _options$option : defaultValue;
            if (!_t) {
              _context.n = 4;
              break;
            }
            _context.n = 3;
            return validate();
          case 3:
            results = _context.v;
          case 4:
            fieldArr = Object.keys(fields).filter(function (field) {
              var _fields$field;
              return (_fields$field = fields[field]) === null || _fields$field === void 0 || (_fields$field = _fields$field.options) === null || _fields$field === void 0 ? void 0 : _fields$field[option];
            }) || [];
            _t2 = isNotEmpty(fieldArr);
            if (!_t2) {
              _context.n = 6;
              break;
            }
            _context.n = 5;
            return validate(fieldArr);
          case 5:
            results = _context.v;
          case 6:
            return _context.a(2, results);
        }
      }, _callee);
    }));
    return function validateOn(_x, _x2) {
      return _ref3.apply(this, arguments);
    };
  }();
  var validateFieldOn = function validateFieldOn(field, fieldOptions, option, defaultValue) {
    var _fieldOptions$option, _options$option2;
    ((_fieldOptions$option = fieldOptions === null || fieldOptions === void 0 ? void 0 : fieldOptions[option]) !== null && _fieldOptions$option !== void 0 ? _fieldOptions$option : isFieldValidate(field, (_options$option2 = options[option]) !== null && _options$option2 !== void 0 ? _options$option2 : defaultValue)) && validate(field);
  };
  var defineField = function defineField(field, fieldOptions) {
    var _fields$field2, _resolve;
    if (!field) {
      //console.warn('The `name` attribute is required for the field definition.');

      return []; // prevent errors
    }
    (_fields$field2 = fields[field]) === null || _fields$field2 === void 0 || _fields$field2._watcher.stop();
    _states[field] || (_states[field] = getInitialState(field, fieldOptions === null || fieldOptions === void 0 ? void 0 : fieldOptions.initialValue));
    var props = mergeProps((_resolve = resolve(fieldOptions, _states[field])) === null || _resolve === void 0 ? void 0 : _resolve.props, resolve(fieldOptions === null || fieldOptions === void 0 ? void 0 : fieldOptions.props, _states[field]), {
      name: field,
      onBlur: function onBlur() {
        _states[field].touched = true;
        validateFieldOn(field, fieldOptions, 'validateOnBlur');
      },
      onInput: function onInput(event) {
        _states[field].value = event && Object.hasOwn(event, 'value') ? event.value : event.target.value;
      },
      onChange: function onChange(event) {
        _states[field].value = event && Object.hasOwn(event, 'value') ? event.value : event.target.type === 'checkbox' || event.target.type === 'radio' ? event.target.checked : event.target.value;
      },
      onInvalid: function onInvalid(errors) {
        var _errors$;
        _states[field].invalid = true;
        _states[field].errors = errors;
        _states[field].error = (_errors$ = errors === null || errors === void 0 ? void 0 : errors[0]) !== null && _errors$ !== void 0 ? _errors$ : null;
      }
    });
    var _watcher = watchPausable(function () {
      return _states[field].value;
    }, function (newValue, oldValue) {
      if (_states[field].pristine) {
        _states[field].pristine = false;
      }
      if (newValue !== oldValue) {
        _states[field].dirty = true;
      }
      validateFieldOn(field, fieldOptions, 'validateOnValueUpdate', true);
    });
    fields[field] = {
      props: props,
      states: _states[field],
      options: fieldOptions,
      _watcher: _watcher
    };
    return [_states[field], props];
  };
  var handleSubmit = function handleSubmit(callback) {
    return /*#__PURE__*/function () {
      var _ref4 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2(event) {
        var results;
        return _regenerator().w(function (_context2) {
          while (1) switch (_context2.n) {
            case 0:
              _context2.n = 1;
              return validateOn('validateOnSubmit', true);
            case 1:
              results = _context2.v;
              return _context2.a(2, callback(_objectSpread({
                originalEvent: event,
                valid: toValue(valid),
                states: toValue(states),
                reset: reset
              }, results)));
          }
        }, _callee2);
      }));
      return function (_x3) {
        return _ref4.apply(this, arguments);
      };
    }();
  };
  var handleReset = function handleReset(callback) {
    return /*#__PURE__*/function () {
      var _ref5 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee3(event) {
        return _regenerator().w(function (_context3) {
          while (1) switch (_context3.n) {
            case 0:
              reset();
              return _context3.a(2, callback({
                originalEvent: event
              }));
          }
        }, _callee3);
      }));
      return function (_x4) {
        return _ref5.apply(this, arguments);
      };
    }();
  };
  var validate = /*#__PURE__*/function () {
    var _ref6 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee4(field) {
      var _yield$options$resolv, _options$resolver, _result, _result$errors;
      var resolverOptions, _ref9, names, values, result, flattenFields, _i, _Object$entries, _Object$entries$_i, fieldName, fieldInst, _fieldInst$options, _getValueByPath, _errors$2, fieldResolver, _yield$fieldResolver, fieldValue, fieldResult, errors, _t3, _t4, _t5, _t6, _t7, _t8;
      return _regenerator().w(function (_context4) {
        while (1) switch (_context4.n) {
          case 0:
            resolverOptions = Object.entries(_states).reduce(function (acc, _ref7) {
              var _ref8 = _slicedToArray(_ref7, 2),
                key = _ref8[0],
                val = _ref8[1];
              acc.names.push(key);
              acc.values[key] = val.value;
              return acc;
            }, {
              names: [],
              values: {}
            });
            _ref9 = [resolverOptions.names, groupKeys(resolverOptions.values)], names = _ref9[0], values = _ref9[1];
            _context4.n = 1;
            return (_options$resolver = options.resolver) === null || _options$resolver === void 0 ? void 0 : _options$resolver.call(options, {
              names: names,
              values: values
            });
          case 1:
            _t4 = _yield$options$resolv = _context4.v;
            _t3 = _t4 !== null;
            if (!_t3) {
              _context4.n = 2;
              break;
            }
            _t3 = _yield$options$resolv !== void 0;
          case 2:
            if (!_t3) {
              _context4.n = 3;
              break;
            }
            _t5 = _yield$options$resolv;
            _context4.n = 4;
            break;
          case 3:
            _t5 = {
              values: values
            };
          case 4:
            result = _t5;
            (_result$errors = (_result = result).errors) !== null && _result$errors !== void 0 ? _result$errors : _result.errors = {};
            flattenFields = [field].flat();
            _i = 0, _Object$entries = Object.entries(fields);
          case 5:
            if (!(_i < _Object$entries.length)) {
              _context4.n = 12;
              break;
            }
            _Object$entries$_i = _slicedToArray(_Object$entries[_i], 2), fieldName = _Object$entries$_i[0], fieldInst = _Object$entries$_i[1];
            if (!(flattenFields.includes(fieldName) || !field || isEmpty(result.errors))) {
              _context4.n = 11;
              break;
            }
            fieldResolver = (_fieldInst$options = fieldInst.options) === null || _fieldInst$options === void 0 ? void 0 : _fieldInst$options.resolver;
            if (!fieldResolver) {
              _context4.n = 10;
              break;
            }
            fieldValue = fieldInst.states.value;
            _context4.n = 6;
            return fieldResolver({
              values: fieldValue,
              value: fieldValue,
              name: fieldName
            });
          case 6:
            _t7 = _yield$fieldResolver = _context4.v;
            _t6 = _t7 !== null;
            if (!_t6) {
              _context4.n = 7;
              break;
            }
            _t6 = _yield$fieldResolver !== void 0;
          case 7:
            if (!_t6) {
              _context4.n = 8;
              break;
            }
            _t8 = _yield$fieldResolver;
            _context4.n = 9;
            break;
          case 8:
            _t8 = {
              values: fieldValue
            };
          case 9:
            fieldResult = _t8;
            isArray(fieldResult.errors) && (fieldResult.errors = _defineProperty({}, fieldName, fieldResult.errors));
            result = mergeKeys(result, fieldResult);
          case 10:
            errors = (_getValueByPath = getValueByPath(result.errors, fieldName)) !== null && _getValueByPath !== void 0 ? _getValueByPath : []; //const value = result.values?.[fieldName] ?? _states[sField].value;
            _states[fieldName].invalid = errors.length > 0;
            _states[fieldName].valid = !_states[fieldName].invalid;
            _states[fieldName].errors = errors;
            _states[fieldName].error = (_errors$2 = errors === null || errors === void 0 ? void 0 : errors[0]) !== null && _errors$2 !== void 0 ? _errors$2 : null;
            //states[fieldName].value = value;
          case 11:
            _i++;
            _context4.n = 5;
            break;
          case 12:
            return _context4.a(2, _objectSpread(_objectSpread({}, result), {}, {
              errors: groupKeys(result.errors)
            }));
        }
      }, _callee4);
    }));
    return function validate(_x5) {
      return _ref6.apply(this, arguments);
    };
  }();
  var reset = function reset() {
    Object.keys(_states).forEach(/*#__PURE__*/function () {
      var _ref0 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee5(field) {
        var _fields$field3;
        var watcher;
        return _regenerator().w(function (_context5) {
          while (1) switch (_context5.n) {
            case 0:
              watcher = fields[field]._watcher;
              watcher.pause();
              fields[field].states = _states[field] = getInitialState(field, (_fields$field3 = fields[field]) === null || _fields$field3 === void 0 || (_fields$field3 = _fields$field3.options) === null || _fields$field3 === void 0 ? void 0 : _fields$field3.initialValue);
              _context5.n = 1;
              return nextTick();
            case 1:
              watcher.resume();
            case 2:
              return _context5.a(2);
          }
        }, _callee5);
      }));
      return function (_x6) {
        return _ref0.apply(this, arguments);
      };
    }());
  };
  var setFieldValue = function setFieldValue(field, value) {
    if (_states[field] !== undefined) _states[field].value = value;
  };
  var getFieldState = function getFieldState(field) {
    var _fields$field4;
    return (_fields$field4 = fields[field]) === null || _fields$field4 === void 0 ? void 0 : _fields$field4.states;
  };
  var setValues = function setValues(values) {
    Object.keys(values).forEach(function (field) {
      return setFieldValue(field, values[field]);
    });
  };
  var validateOnMounted = function validateOnMounted() {
    validateOn('validateOnMount');
  };
  tryOnMounted(validateOnMounted);
  return {
    defineField: defineField,
    setFieldValue: setFieldValue,
    getFieldState: getFieldState,
    handleSubmit: handleSubmit,
    handleReset: handleReset,
    validate: validate,
    setValues: setValues,
    reset: reset,
    valid: valid,
    states: states,
    fields: fields
  };
};

export { useForm };
//# sourceMappingURL=index.mjs.map
