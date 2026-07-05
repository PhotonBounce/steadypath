package com.photonbounce.leads.db

import androidx.room.*
import kotlinx.coroutines.flow.Flow

@Dao
interface MicrositeDao {
    @Query("SELECT * FROM microsites ORDER BY createdAt DESC")
    fun getAllMicrosites(): Flow<List<MicrositeEntity>>

    @Query("SELECT * FROM microsites WHERE active = 1 ORDER BY createdAt DESC")
    fun getActiveMicrosites(): Flow<List<MicrositeEntity>>

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertAll(microsites: List<MicrositeEntity>)

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(microsite: MicrositeEntity)

    @Update
    suspend fun update(microsite: MicrositeEntity)

    @Query("DELETE FROM microsites")
    suspend fun clearAll()

    @Query("SELECT COUNT(*) FROM microsites")
    suspend fun getCount(): Int
}
