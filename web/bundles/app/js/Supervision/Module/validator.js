/**
 * Created by mchrif on 17/03/2016.
 */

$(function () {

    $.validator.addMethod(
        "regex",
        function(value, element, regexp) {
            var re = new RegExp(regexp);
            return this.optional(element) || re.test(value);
        },
        Translator.trans("general_validation.invalid_format")
    );

});