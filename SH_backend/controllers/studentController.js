import pool from '../config/dbConfig.js';

export async function getAllStudents(req, res) {
    try {
        const connection = await pool.getConnection();
        const [rows] = await connection.query('SELECT * FROM students');
        connection.release();
        res.json(rows);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
}

export async function saveStudent(req, res) {
    try {
        const { name, email, phone } = req.body;
        const connection = await pool.getConnection();
        const [result] = await connection.query('INSERT INTO students (name, email, phone) VALUES (?, ?, ?)', 
            [name, email, phone]);
        connection.release();
        res.json({ message: "Student saved", id: result.insertId });
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
}

export async function updateStudent(req, res) {
    try {
        const { id } = req.params;
        const { name, email, phone } = req.body;
        const connection = await pool.getConnection();
        await connection.query('UPDATE students SET name = ?, email = ?, phone = ? WHERE id = ?', 
            [name, email, phone, id]);
        connection.release();
        res.json({ message: "Student updated" });
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
}

export async function deleteStudent(req, res) {
    try {
        const { id } = req.params;
        const connection = await pool.getConnection();
        await connection.query('DELETE FROM students WHERE id = ?', [id]);
        connection.release();
        res.json({ message: "Student deleted" });
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
}