import express from 'express';
import bodyParser from 'body-parser';
import studentRouter from './routes/studentRouter.js';

let app = express();

app.use(bodyParser.json())

app.use("/student", studentRouter);

app.listen(5000,
    ()=>{
        console.log("Server is running on port 5000.")
    }
)