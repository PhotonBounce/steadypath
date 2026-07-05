package com.photonbounce.leads.db

import androidx.room.*
import kotlinx.coroutines.flow.Flow

@Dao
interface LeadDao {
    @Query("SELECT * FROM leads ORDER BY createdAt DESC")
    fun getAllLeads(): Flow<List<LeadEntity>>

    @Query("SELECT * FROM leads WHERE status = :status ORDER BY createdAt DESC")
    fun getLeadsByStatus(status: String): Flow<List<LeadEntity>>

    @Query("SELECT * FROM leads WHERE name LIKE '%' || :query || '%' OR email LIKE '%' || :query || '%' ORDER BY createdAt DESC")
    fun searchLeads(query: String): Flow<List<LeadEntity>>

    @Query("SELECT * FROM leads WHERE id = :id LIMIT 1")
    suspend fun getLeadById(id: String): LeadEntity?

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insertAll(leads: List<LeadEntity>)

    @Insert(onConflict = OnConflictStrategy.REPLACE)
    suspend fun insert(lead: LeadEntity)

    @Update
    suspend fun update(lead: LeadEntity)

    @Query("DELETE FROM leads")
    suspend fun clearAll()

    @Query("SELECT * FROM leads WHERE isSynced = 0")
    suspend fun getUnsyncedLeads(): List<LeadEntity>

    @Query("UPDATE leads SET isSynced = 1, pendingStatus = NULL, pendingNotes = NULL WHERE id = :id")
    suspend fun markSynced(id: String)
}
