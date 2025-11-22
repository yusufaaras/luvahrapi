import { Schema, model, Document } from "mongoose";

export interface ICV extends Document {
  name?: string;
  email?: string;
  phone?: string;
  section_title?: string;
  subject?: string;
  message?: string;
  file?: {
    originalname: string;
    encoding?: string;
    mimetype?: string;
    size?: number;
    filename?: string;
    path?: string;
  } | null;
  submittedAt: Date;
}

const CVSchema = new Schema<ICV>({
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
  submittedAt: { type: Date, default: () => new Date() }
});

export const CVModel = model<ICV>("CV", CVSchema);
export default CVModel;