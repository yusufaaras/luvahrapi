import type { Request, Response } from "express";

// require kullanarak import yapıyoruz — ts-node-dev ile interop problemlerini önlemek için
const express = require("express");
const multer = require("multer");
const path = require("path");
const fs = require("fs");

// models'i require ile alıyoruz
const { CVModel } = require("../models/CV");

const router = express.Router();

// uploads klasörü
const uploadsDir = path.resolve(process.cwd(), "uploads");
if (!fs.existsSync(uploadsDir)) fs.mkdirSync(uploadsDir);

const storage = multer.diskStorage({
  destination: function (_req: any, _file: any, cb: any) {
    cb(null, uploadsDir);
  },
  filename: function (_req: any, file: any, cb: any) {
    const unique = Date.now() + "-" + Math.round(Math.random() * 1e9);
    const safeName = file.originalname.replace(/\s+/g, "_");
    cb(null, `${unique}-${safeName}`);
  }
});

const upload = multer({
  storage,
  limits: {
    fileSize: 10 * 1024 * 1024 // 10MB
  }
});

router.post("/submit-cv", upload.any(), async (req: Request, res: Response) => {
  try {
    const body: any = req.body || {};
    const files = (req as any).files as Express.Multer.File[] | undefined;
    const file = files && files.length > 0 ? files[0] : null;

    const doc = new CVModel({
      name: body.name,
      email: body.email,
      phone: body.phone,
      section_title: body.section_title || body.sectionTitle || null,
      subject: body.subject || null,
      message: body.message || null,
      file: file
        ? {
            originalname: file.originalname,
            encoding: (file as any).encoding,
            mimetype: file.mimetype,
            size: file.size,
            filename: file.filename || path.basename(file.path),
            path: file.path
          }
        : null,
      submittedAt: new Date()
    });

    await doc.save();

    return res.status(200).json({ success: true, message: "CV başarıyla kaydedildi." });
  } catch (err) {
    // eslint-disable-next-line no-console
    console.error("submit-cv error:", err);
    return res.status(500).json({ success: false, message: "Sunucu hatası" });
  }
});

export default router;