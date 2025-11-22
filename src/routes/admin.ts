import type { Request, Response } from "express";
const express = require("express");
const { CVModel } = require("../models/CV");

const router = express.Router();

// Tüm CV kayıtlarını JSON olarak getirir
router.get("/admin-cv", async (_req: Request, res: Response) => {
  try {
    const cvs = await CVModel.find({}).sort({ submittedAt: -1 });
    return res.status(200).json({ success: true, data: cvs });
  } catch (err) {
    console.error("admin-cv error:", err);
    return res.status(500).json({ success: false, message: "Veri çekilemedi" });
  }
});

export default router;