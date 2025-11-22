"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.CVModel = void 0;
var mongoose_1 = require("mongoose");
var CVSchema = new mongoose_1.Schema({
    name: { type: String },
    email: { type: String },
    phone: { type: String },
    section_title: { type: String },
    subject: { type: String },
    message: { type: String },
    file: {
        originalname: { type: String },
        encoding: { type: String },
        mimetype: { type: String },
        size: { type: Number },
        filename: { type: String },
        path: { type: String }
    },
    submittedAt: { type: Date, default: function () { return new Date(); } }
});
exports.CVModel = (0, mongoose_1.model)("CV", CVSchema);
exports.default = exports.CVModel;
