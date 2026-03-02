import express from 'express';
import { deleteStudent, getAllStudents, saveStudent, updateStudent } from '../controllers/studentController.js';

const studentRouter = express.Router();

studentRouter.get("/", getAllStudents);
studentRouter.get("/:id", /* optional: handler to get one student */);

studentRouter.post("/", saveStudent);

studentRouter.put("/:id", updateStudent);       // <– id parameter
studentRouter.delete("/:id", deleteStudent);    // <– id parameter

export default studentRouter;